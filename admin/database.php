<?php
$rootpath = '../';
require $rootpath."include/bittorrent.php";
loggedinorreturn();
ADMIN::check();
  
if (get_user_class() < UC_SYSOP)
stderr("Error", "Permission denied.");

  function openfilewrite ($filename)
  {
    if (function_exists ('gzopen'))
    {
      $filename .= '.gz';
      $handle = gzopen ($filename, 'w9');
    }
    else
    {
      $handle = fopen ($filename, 'w');
    }

    return $handle;
  }

  function openfileread ($filename)
  {
    if (function_exists ('gzopen'))
    {
      $handle = gzopen ($filename, 'r');
    }
    else
    {
      $handle = fopen ($filename, 'r');
    }

    return $handle;
  }

  function writefiledata ($handle, $data)
  {
    if (function_exists ('gzwrite'))
    {
      gzwrite ($handle, $data);
      return;
    }

    fwrite ($handle, $data);
  }

  function readfiledata ($handle, $size)
  {
    if (function_exists ('gzread'))
    {
      $data = gzread ($handle, $size);
    }
    else
    {
      $data = fread ($handle, $size);
    }

    return $data;
  }

  function eof ($handle)
  {
    if (function_exists ('gzeof'))
    {
      return gzeof ($handle);
    }

    return feof ($handle);
  }

  function closefile ($handle)
  {
    if (function_exists ('gzclose'))
    {
      gzclose ($handle);
      return;
    }

    fclose ($handle);
  }

  function backuptable ($tablename, $fp)
  {
    if ((empty ($tablename) OR empty ($fp)))
    {
      $msg = '' . 'Failed to export table \'' . $tablename . '\'<br/>';
      return '';
    }

    $createTable = sql_query ('' . 'SHOW CREATE TABLE `' . $tablename . '`');
    $createTable = mysql_fetch_array ($createTable);
    $tableDump = '' . 'DROP TABLE IF EXISTS `' . $tablename . '`;
' . $createTable['Create Table'] . ';

';
    writefiledata ($fp, $tableDump);
    if ($getRows = sql_query ('' . 'SELECT * FROM `' . $tablename . '`'))
    {
      $fieldCount = mysql_num_fields ($getRows);
      $rowCount = 0;
      while ($row = mysql_fetch_array ($getRows))
      {
        $tableDump = '' . 'INSERT INTO `' . $tablename . '` VALUES(';
        $fieldcounter = 0 - 1;
        $firstfield = true;
        while (++$fieldcounter < $fieldCount)
        {
          if (!$firstfield)
          {
            $tableDump .= ', ';
          }
          else
          {
            $firstfield = 0;
          }

          if (!isset ($row['' . $fieldcounter]))
          {
            $tableDump .= 'NULL';
            continue;
          }
          else
          {
            if ($row['' . $fieldcounter] != '')
            {
              $tableDump .= '\'' . addslashes ($row['' . $fieldcounter]) . '\'';
              continue;
            }
            else
            {
              $tableDump .= '\'\'';
              continue;
            }

            continue;
          }
        }

        $tableDump .= ');
';
        writefiledata ($fp, $tableDump);
        ++$rowCount;
      }

      mysql_free_result ($getRows);
    }

    writefiledata ($fp, '


');
    $msg = '' . 'Exported ' . $rowCount . ' rows from table \'' . $tablename . '\'<br/>';
    return $msg;
  }

  function create_hash ()
  {
    $hash = md5 (uniqid (rand (), TRUE));
    return $hash;
  }

  function backupsingletable ($tablename)
  {
    global $backupDir;
    $msg = '';
    if (!empty ($tablename))
    {
      $path = $backupDir . $tablename . '_' . create_hash () . '.sql';
      if ($fp = openfilewrite ($path))
      {
        $msg = backuptable ($tablename, $fp);
        closefile ($fp);
        $msg .= '<br/>Database backup saved to ' . $path . '<br/>';
      }
      else
      {
        $msg .= '<br/>ERROR: writing to file "' . $path . '" failed!<br/>';
      }
    }

    return $msg;
  }

  function batchbackuptable ($tablenames)
  {
    global $mysql_db;
    global $backupDir;
    $path = $backupDir . $mysql_db . '_' . create_hash () . '.sql';
    $msg = '';
    if ((strlen ($mysql_db) AND $fp = openfilewrite ($path)))
    {
      $i = 0;
      while ($i < count ($tablenames))
      {
        $msg = $msg . backuptable ($tablenames[$i], $fp);
        ++$i;
      }

      closefile ($fp);
      $msg .= '<br/>Database backup saved to ' . $path . '<br/>';
    }
    else
    {
      $msg .= '<br/><strong>Backup writing to "' . $path . '" failed!</strong><br/>';
    }

    return $msg;
  }

  function parsequeries ($sql, $delimiter)
  {
    $matches = array ();
    $output = array ();
    $queries = explode ($delimiter, $sql);
    $sql = '';
    $query_count = count ($queries);
    $i = 0;
    while ($i < $query_count)
    {
      if (($i != $query_count - 1 OR strlen (0 < $queries[$i])))
      {
        $total_quotes = preg_match_all ('/\'/', $queries[$i], $matches);
        $escaped_quotes = preg_match_all ('/(?<!\\\\)(\\\\\\\\)*\\\\\'/', $queries[$i], $matches);
        $unescaped_quotes = $total_quotes - $escaped_quotes;
        if ($unescaped_quotes % 2 == 0)
        {
          $output[] = $queries[$i];
          $queries[$i] = '';
        }
        else
        {
          $temp = $queries[$i] . $delimiter;
          $queries[$i] = '';
          $complete_stmt = false;
          $j = $i + 1;
          while ((!$complete_stmt AND $j < $query_count))
          {
            $total_quotes = preg_match_all ('/\'/', $queries[$j], $matches);
            $escaped_quotes = preg_match_all ('/(?<!\\\\)(\\\\\\\\)*\\\\\'/', $queries[$j], $matches);
            $unescaped_quotes = $total_quotes - $escaped_quotes;
            if ($unescaped_quotes % 2 == 1)
            {
              $output[] = $temp . $queries[$j];
              $queries[$j] = '';
              $temp = '';
              $complete_stmt = true;
              $i = $j;
            }
            else
            {
              $temp .= $queries[$j] . $delimiter;
              $queries[$j] = '';
            }

            ++$j;
          }
        }
      }

      ++$i;
    }

    return $output;
  }

  function restorebackup ($filename)
  {
    global $backupDir;
    if ($fp = openfileread ($backupDir . $filename))
    {
      $query = '';
      while (!eof ($fp))
      {
        $query .= readfiledata ($fp, 10000);
      }

      closefile ($fp);
      $queries = parsequeries ($query, ';');
      if ($cnt = count ($queries))
      {
        $inserts = 0;
        $i = 0;
        while ($i < $cnt)
        {
          $sql = trim ($queries[$i]);
          if (!empty ($sql))
          {
            if (substr ($sql, 0, 6) == 'INSERT')
            {
              ++$inserts;
            }

            sql_query ($sql);
          }

          ++$i;
        }
      }

      redirect ('admin/database.php?act=database', '' . '<strong>Processed ' . $cnt . ' statements in total.<br />' . $inserts . ' rows added in total.</strong>', '', 5);
      return;
    }

    redirect ('admin/database.php?act=database', '' . '<strong>Failed to open backup file \'' . $filename . '\'!</strong>', '', 4);
  }

  function deletebackup ($filename)
  {
    global $backupDir;
    $fname = $backupDir . $filename;
    if (is_file ($fname))
    {
      if (!@unlink ($fname))
      {
        echo '' . '<strong>Failed to remove backup file \'' . $fname . '\'!</strong>';
      }
    }

    redirect ('admin/database.php?act=database', $fname . ' has been deleted.', '', 4);
  }

  function _stdmsg ($title, $message)
  {
    return array ('title' => $title, 'message' => $message);
  }

  function tableoperation ($tablename, $OP)
  {
    if ((!empty ($tablename) AND ereg ('' . '^CHECK$|^OPTIMIZE$|^REPAIR$', $OP)))
    {
      $result = sql_query ('' . $OP . ' TABLE `' . $tablename . '`');
      $result = mysql_fetch_array ($result);
      return 'Operation on table \'' . $tablename . '\' Result: <strong>' . $result['Msg_text'] . '</strong><br/>';
    }

    return '<strong>Invalid table operation!</strong>';
  }

  function batchtableoperation ($tablenames, $OP)
  {
    $msg = '';
    if (((!empty ($tablenames) AND !empty ($OP)) AND ereg ('' . '^CHECK$|^OPTIMIZE$|^REPAIR$', $OP)))
    {
      $i = 0;
      while ($i < count ($tablenames))
      {
        $msg = $msg . tableoperation ($tablenames[$i], $OP);
        ++$i;
      }
    }
    else
    {
      $msg = '<strong>No tables specified or invalid operation!</strong>';
    }

    return $msg;
  }

  function printinstructions ()
  {
    
	echo '<table width="70%" border="0" cellpadding="5" cellspacing="0">
		<tr>
		<td class="colhead">
			Ruff Tracker Database Operation Tool  
		</td>
        <tr>
          <td class="tdrow2">
          You can use this tool to backup and maintain your Ruff Tracker databases. The maintenance commands available are :-<br />
          <strong>Check</strong> - Checks the table for errors<br />
          <strong>Optimize</strong> - Removes any wasted space (as reported in the \'Overhead\' column)<br />
          <strong>Repair</strong> - Attempts to recover errors in the table<br /><br />
          You can also use this tool to backup your database tables as SQL files which can be used to recover the database in the event of data loss.
          </td>
        </tr>
        </table><br>';
  }

  function displaybackups ()
  {
    global $dateformat;
    global $timeformat;
    global $backupDir;
    global $backupUrl;
    global $backupEnabled;
    global $_this_script_;
    clearstatcache ();
    echo '<br><table width="70%" border="0" cellpadding="5" cellspacing="0">
        <tr>
          <td class="xxxx" colspan="6">Backup Directory: "' . $backupDir . '"</td>
        </tr>
        <tr>
          <td class="colhead" width="50%" align="left">File Name</td>
          <td class="colhead" align="center">Size</td>
          <td class="colhead" align="center">Last Modified</td>
          <td class="colhead" colspan="3" align="center">Operations</td>';
    $filecount = 0;
    if ($dir = @opendir ($backupDir))
    {
      while (false !== $file = readdir ($dir))
      {
        if ((substr ($file, 0, 1) != '.' AND 0 < strpos (strtolower ($file), '.sql')))
        {
          if ($stats = @stat ($backupDir . $file))
          {
            $filestats[] = array ('file' => $file, 'size' => $stats['size'], 'mtime' => $stats['mtime'], 'error' => false);
          }
          else
          {
            $filestats[] = array ('file' => $file, 'error' => true);
          }

          ++$filecount;
          continue;
        }
      }

      @sort ($filestats);
      $i = 0;
      while ($i < count ($filestats))
      {
        if (empty ($filestats[$i]['error']))
        {
          echo '<tr>
            <td class="tdrow3" align="left">' . $filestats[$i]['file'] . '</td>
            <td class="tdrow3"align="center">' . mksize ($filestats[$i]['size']) . '</td>
            <td class="tdrow3"align="center">' . date("Y-m-d G:i:s",$filestats[$i]['mtime']) . ' ' . '</td>
            <td class="tdrow3"align="center"><a href="' . $_this_script_ . 'database.php?act=database&dbaction=restorebackup&filename=' . $filestats[$i]['file'] . '" onclick="return confirm(\'Are you sure you wish to restore this backup file?\\nAll data entered after backup date will be deleted.\');">Restore</a></td>
            <td class="tdrow3"align="center"><a href="' . $backupUrl . $filestats[$i]['file'] . '">Download</a></td>
            <td class="tdrow3"align="center"><a href="' . $_this_script_ . 'database.php?act=database&dbaction=deletebackup&filename=' . $filestats[$i]['file'] . '" onclick="return confirm(\'Are you sure you wish to delete this backup file?\');">Delete</a></td>
          </tr>';
        }
        else
        {
          echo '<tr>
            <td class="tdrow3">' . $filestats[$i]['file'] . '</td>
            <td class="tdrow3" colspan="5">No info available (permissions wrong?).</td>
          </tr>';
        }

        ++$i;
      }

      if ($filecount <= 0)
      {
        echo '<tr><td colspan="4">No backup file found!</td></tr>';
      }
    }

    echo '</table>';
  }

  function displaytables ()
  {
    global $mysql_db;
    global $backupEnabled;
    global $_this_script_;
    global $errors;
    printinstructions ();
    if ((!empty ($errors) AND is_array ($errors)))
    {
      $showerror = _stdmsg ('Configuration Error', implode ('<BR>', $errors));
      stdmsg ($showerror['title'], $showerror['message']);
    }

    echo '<form method="post" action="' . $_this_script_ . '" name="tables">
      <input type="hidden" name="dbaction" value=""/>';
    echo '<table width="70%" border="0" cellpadding="5" cellspacing="0">
      <tr>
        <td class="colhead"><input type="checkbox" checkall="group" onclick="javascript: return select_deselectAll (\'tables\', this, \'group\');"></td>
                <td class="colhead">Table Name</td>
                <td class="colhead">Rows</td>
                <td class="colhead">Data Length</td>
                <td class="colhead">Index Length</td>
                <td class="colhead">Overhead</td>
                <td class="colhead" colspan="4" align="center">Operations</td>
      </tr>';
    if ($gettables = sql_query ('SHOW TABLES FROM `' . $mysql_db . '`'))
    {
      while ($table = mysql_fetch_array ($gettables))
      {
        $tableinfo = sql_query ('SHOW TABLE STATUS LIKE \'' . $table[0] . '\'');
        $tableinfo = mysql_fetch_array ($tableinfo);
        echo '<tr>
          <td class="tdrow3" align="center"><input type="checkbox" name="tablenames[]" value="' . $tableinfo['Name'] . '" checkme="group" /></td>
          <td class="tdrow3" align="left">' . $tableinfo['Name'] . '</td>
                  <td class="tdrow3" align="center">' . $tableinfo['Rows'] . '</td>
                  <td class="tdrow3" align="center">' . mksize ($tableinfo['Data_length']) . '</td>
                  <td class="tdrow3" align="center">' . mksize ($tableinfo['Index_length']) . '</td>
                  <td class="tdrow3" align="center">' . (!empty ($tableinfo['Data_free']) ? '<b><font color="red">' : '') . mksize ($tableinfo['Data_free']) . (!empty ($tableinfo['Data_free']) ? '</font></b>' : '') . '</td>
                  <td class="tdrow3" align="center"><a href="' . $_this_script_ . 'database.php?act=database&dbaction=checktable&tablename=' . $tableinfo['Name'] . '"><font color=blue>Check</font></a></td>
                  <td class="tdrow3" align="center"><a href="' . $_this_script_ . 'database.php?act=database&dbaction=optimizetable&tablename=' . $tableinfo['Name'] . '"><font color=green>Optimize</font></a></td>
                  <td class="tdrow3" align="center"><a href="' . $_this_script_ . 'database.php?act=database&dbaction=repairtable&tablename=' . $tableinfo['Name'] . '"><font color=red>Repair</font></a></td>
                  <td class="tdrow3" align="center">' . ($backupEnabled ? '<a href="' . $_this_script_ . 'database.php?act=database&dbaction=backuptable&tablename=' . $tableinfo['Name'] . '"><font color=purple>Backup</font></a>' : '<font color=gray>Backup</font>') . '</td>
        </tr>';
      }

      mysql_free_result ($gettables);
    }

    echo '<tr>
        <td class="tdrow1" colspan="6">&nbsp;</td>
                <td class="tdrow1"><input type="submit" value="Check" onclick="document.forms[\'tables\'].dbaction.value = \'checkall\';"/></td>
                <td class="tdrow1"><input type="submit" value="Optimize" onclick="document.forms[\'tables\'].dbaction.value = \'optimizeall\';"/></td>
                <td class="tdrow1"><input type="submit" value="Repair" onclick="document.forms[\'tables\'].dbaction.value = \'repairall\';"/></td>
                <td class="tdrow1"><input type="submit" value="Backup" onclick="document.forms[\'tables\'].dbaction.value = \'backupall\';" ' . ($backupEnabled ? '' : 'disabled') . '/></td>
      </tr>
    </table>
    </form>';
    displaybackups ();
  }

  

  define ('D_VERSION', '0.2');
  $backupEnabled = false;
  $backupDir = $rootpath.'fts-contents/backup/';
  $backupUrl = '' . $BASEURL . '/fts-contents/backup/';
  $errors = array ();
  if (!is_dir ($backupDir))
  {
    $errors[] = 'Backup Directory (' . $backupDir . ') does not exist';
  }
  else
  {
    if (!is_writable ($backupDir))
    {
      $errors[] = 'Backup Directory (' . $backupDir . ') is not writable - chmod to 0777';
    }
  }

  $action = (!empty ($_POST['dbaction']) ? $_POST['dbaction'] : (!empty ($_GET['dbaction']) ? $_GET['dbaction'] : ''));
  $tablename = (!empty ($_POST['tablename']) ? $_POST['tablename'] : (!empty ($_GET['tablename']) ? $_GET['tablename'] : ''));
  $filename = (!empty ($_POST['filename']) ? $_POST['filename'] : (!empty ($_GET['filename']) ? $_GET['filename'] : ''));
  $tablenames = ((!empty ($_POST['tablenames']) AND is_array ($_POST['tablenames'])) ? $_POST['tablenames'] : '');
  if (!empty ($errors))
  {
    unset ($action);
    if (!empty ($action))
    {
      $errors[] = 'Action cancelled!';
    }
  }
  else
  {
    $backupEnabled = true;
  }

  if (!empty ($action))
  {
    switch ($action)
    {
      case 'checktable':
      {
        $showact = _stdmsg ('Check Table Results', tableoperation ($tablename, 'CHECK'));
        break;
      }

      case 'checkall':
      {
        $showact = _stdmsg ('Check Table Results', batchtableoperation ($tablenames, 'CHECK'));
        break;
      }

      case 'optimizetable':
      {
        $showact = _stdmsg ('Optimize Table Results', tableoperation ($tablename, 'OPTIMIZE'));
        break;
      }

      case 'optimizeall':
      {
        $showact = _stdmsg ('Optimize Table Results', batchtableoperation ($tablenames, 'OPTIMIZE'));
        break;
      }

      case 'repairtable':
      {
        $showact = _stdmsg ('Repair Table Results', tableoperation ($tablename, 'REPAIR'));
        break;
      }

      case 'repairall':
      {
        $showact = _stdmsg ('Repair Table Results', batchtableoperation ($tablenames, 'REPAIR'));
        break;
      }

      case 'backuptable':
      {
        $showact = _stdmsg ('Backup Table Results', backupsingletable ($tablename));
        break;
      }

      case 'backupall':
      {
        $showact = _stdmsg ('Backup Table Results', batchbackuptable ($tablenames));
        break;
      }

      case 'restorebackup':
      {
        restorebackup ($filename);
        break;
      }

      case 'deletebackup':
      {
        deletebackup ($filename);
      }
    }
  }

  stdhead ();
  if (isset ($showact))
  {
    stdmsg ($showact['title'], $showact['message'], false, 'success');
  }

  displaytables ();
  stdfoot ();
?>
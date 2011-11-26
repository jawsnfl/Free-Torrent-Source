<?php
/**
 * Part of Free Torrent Source.
 * This script is open-source
 * You can modify the code bellow, but try to keep it as we made it if you
 * don't know PHP/MYSQL/HTML  
 * */  
/**
 * InputFilter
 *
 * @package Free Torrent Source
 * @author Filip
 * @copyright 2008
 * @version $Id$
 * @access public
 */
class InputFilter
{
    var $tagsArray ;
    var $attrArray ;
    var $tagsMethod ;
    var $attrMethod ;
    var $xssAuto ;
    var $tagBlacklist = array( 'applet', 'body', 'bgsound', 'base', 'basefont',
        'embed', 'frame', 'frameset', 'head', 'html', 'id', 'iframe', 'ilayer', 'layer',
        'link', 'meta', 'name', 'object', 'script', 'style', 'title', 'xml' ) ;
    var $attrBlacklist = array( 'action', 'background', 'codebase', 'dynsrc',
        'lowsrc' ) ;
  /**
   * InputFilter::inputFilter()
   *
   * @param mixed $tagsArray
   * @param mixed $attrArray
   * @param integer $tagsMethod
   * @param integer $attrMethod
   * @param integer $xssAuto
   * @return
   */
    function inputFilter( $tagsArray = array(), $attrArray = array(), $tagsMethod =
        0, $attrMethod = 0, $xssAuto = 1 )
    {
        for ( $i = 0; $i < count($tagsArray); $i++ )
            $tagsArray[$i] = strtolower( $tagsArray[$i] ) ;
        for ( $i = 0; $i < count($attrArray); $i++ )
            $attrArray[$i] = strtolower( $attrArray[$i] ) ;
        $this->tagsArray = ( array )$tagsArray ;
        $this->attrArray = ( array )$attrArray ;
        $this->tagsMethod = $tagsMethod ;
        $this->attrMethod = $attrMethod ;
        $this->xssAuto = $xssAuto ;
    }
  /**
   * InputFilter::process()
   *
   * @param mixed $source
   * @return
   */
    function process( $source )
    {
        if ( is_array($source) )
        {
            foreach ( $source as $key => $value )
                if ( is_string($value) )
                    $source[$key] = $this->remove( $this->decode($value) ) ;
            return $source ;
        }
        else
            if ( is_string($source) )
            {
                return $this->remove( $this->decode($source) ) ;
            }
            else
                return $source ;
    }
  /**
   * InputFilter::remove()
   *
   * @param mixed $source
   * @return
   */
    function remove( $source )
    {
        $loopCounter = 0 ;
        while ( $source != $this->filterTags($source) )
        {
            $source = $this->filterTags( $source ) ;
            $loopCounter++ ;
        }
        return $source ;
    }
  /**
   * InputFilter::filterTags()
   *
   * @param mixed $source
   * @return
   */
    function filterTags( $source )
    {
        $preTag = null ;
        $postTag = $source ;
        $tagOpen_start = strpos( $source, '<' ) ;
        while ( $tagOpen_start !== false )
        {
            $preTag .= substr( $postTag, 0, $tagOpen_start ) ;
            $postTag = substr( $postTag, $tagOpen_start ) ;
            $fromTagOpen = substr( $postTag, 1 ) ;
            $tagOpen_end = strpos( $fromTagOpen, '>' ) ;
            if ( $tagOpen_end === false )
                break ;
            $tagOpen_nested = strpos( $fromTagOpen, '<' ) ;
            if ( ($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end) )
            {
                $preTag .= substr( $postTag, 0, ($tagOpen_nested + 1) ) ;
                $postTag = substr( $postTag, ($tagOpen_nested + 1) ) ;
                $tagOpen_start = strpos( $postTag, '<' ) ;
                continue ;
            }
            $tagOpen_nested = ( strpos($fromTagOpen, '<') + $tagOpen_start + 1 ) ;
            $currentTag = substr( $fromTagOpen, 0, $tagOpen_end ) ;
            $tagLength = strlen( $currentTag ) ;
            if ( ! $tagOpen_end )
            {
                $preTag .= $postTag ;
                $tagOpen_start = strpos( $postTag, '<' ) ;
            }
            $tagLeft = $currentTag ;
            $attrSet = array() ;
            $currentSpace = strpos( $tagLeft, ' ' ) ;
            if ( substr($currentTag, 0, 1) == "/" )
            {
                $isCloseTag = true ;
                list( $tagName ) = explode( ' ', $currentTag ) ;
                $tagName = substr( $tagName, 1 ) ;
            }
            else
            {
                $isCloseTag = false ;
                list( $tagName ) = explode( ' ', $currentTag ) ;
            }
            if ( (! preg_match("/^[a-z][a-z0-9]*$/i", $tagName)) || (! $tagName) || ((in_array
                (strtolower($tagName), $this->tagBlacklist)) && ($this->xssAuto)) )
            {
                $postTag = substr( $postTag, ($tagLength + 2) ) ;
                $tagOpen_start = strpos( $postTag, '<' ) ;
                continue ;
            }
            while ( $currentSpace !== false )
            {
                $fromSpace = substr( $tagLeft, ($currentSpace + 1) ) ;
                $nextSpace = strpos( $fromSpace, ' ' ) ;
                $openQuotes = strpos( $fromSpace, '"' ) ;
                $closeQuotes = strpos( substr($fromSpace, ($openQuotes + 1)), '"' ) + $openQuotes +
                    1 ;
                if ( strpos($fromSpace, '=') !== false )
                {
                    if ( ($openQuotes !== false) && (strpos(substr($fromSpace, ($openQuotes + 1)),
                        '"') !== false) )
                        $attr = substr( $fromSpace, 0, ($closeQuotes + 1) ) ;
                    else
                        $attr = substr( $fromSpace, 0, $nextSpace ) ;
                }
                else
                    $attr = substr( $fromSpace, 0, $nextSpace ) ;
                if ( ! $attr )
                    $attr = $fromSpace ;
                $attrSet[] = $attr ;
                $tagLeft = substr( $fromSpace, strlen($attr) ) ;
                $currentSpace = strpos( $tagLeft, ' ' ) ;
            }
            $tagFound = in_array( strtolower($tagName), $this->tagsArray ) ;
            if ( (! $tagFound && $this->tagsMethod) || ($tagFound && ! $this->tagsMethod) )
            {
                if ( ! $isCloseTag )
                {
                    $attrSet = $this->filterAttr( $attrSet ) ;
                    $preTag .= '<' . $tagName ;
                    for ( $i = 0; $i < count($attrSet); $i++ )
                        $preTag .= ' ' . $attrSet[$i] ;
                    if ( strpos($fromTagOpen, "</" . $tagName) )
                        $preTag .= '>' ;
                    else
                        $preTag .= ' />' ;
                }
                else
                    $preTag .= '</' . $tagName . '>' ;
            }
            $postTag = substr( $postTag, ($tagLength + 2) ) ;
            $tagOpen_start = strpos( $postTag, '<' ) ;
        }
        $preTag .= $postTag ;
        return $preTag ;
    }
  /**
   * InputFilter::filterAttr()
   *
   * @param mixed $attrSet
   * @return
   */
    function filterAttr( $attrSet )
    {
        $newSet = array() ;
        for ( $i = 0; $i < count($attrSet); $i++ )
        {
            if ( ! $attrSet[$i] )
                continue ;
            $attrSubSet = explode( '=', trim($attrSet[$i]) ) ;
            list( $attrSubSet[0] ) = explode( ' ', $attrSubSet[0] ) ;
            if ( (! eregi("^[a-z]*$", $attrSubSet[0])) || (($this->xssAuto) && ((in_array(strtolower
                ($attrSubSet[0]), $this->attrBlacklist)) || (substr($attrSubSet[0], 0, 2) ==
                'on'))) )
                continue ;
            if ( $attrSubSet[1] )
            {
                $attrSubSet[1] = str_replace( '&#', '', $attrSubSet[1] ) ;
                $attrSubSet[1] = preg_replace( '/\s+/', '', $attrSubSet[1] ) ;
                $attrSubSet[1] = str_replace( '"', '', $attrSubSet[1] ) ;
                if ( (substr($attrSubSet[1], 0, 1) == "'") && (substr($attrSubSet[1], (strlen($attrSubSet[1]) -
                    1), 1) == "'") )
                    $attrSubSet[1] = substr( $attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2) ) ;
                $attrSubSet[1] = stripslashes( $attrSubSet[1] ) ;
            }
            if ( ((strpos(strtolower($attrSubSet[1]), 'expression') !== false) && (strtolower
                ($attrSubSet[0]) == 'style')) || (strpos(strtolower($attrSubSet[1]),
                'javascript:') !== false) || (strpos(strtolower($attrSubSet[1]), 'behaviour:')
                !== false) || (strpos(strtolower($attrSubSet[1]), 'vbscript:') !== false) || (strpos
                (strtolower($attrSubSet[1]), 'mocha:') !== false) || (strpos(strtolower($attrSubSet[1]),
                'livescript:') !== false) )
                continue ;
            $attrFound = in_array( strtolower($attrSubSet[0]), $this->attrArray ) ;
            if ( (! $attrFound && $this->attrMethod) || ($attrFound && ! $this->attrMethod) )
            {
                if ( $attrSubSet[1] )
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[1] . '"' ;
                else
                    if ( $attrSubSet[1] == "0" )
                        $newSet[] = $attrSubSet[0] . '="0"' ;
                    else
                        $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[0] . '"' ;
            }
        }
        return $newSet ;
    }
  /**
   * InputFilter::decode()
   *
   * @param mixed $source
   * @return
   */
    function decode( $source )
    {
        $source = html_entity_decode( $source, ENT_QUOTES, "ISO-8859-1" ) ;
        $source = preg_replace( '/&#(\d+);/me', "chr(\\1)", $source ) ;
        $source = preg_replace( '/&#x([a-f0-9]+);/mei', "chr(0x\\1)", $source ) ;
        return $source ;
    }
  /**
   * InputFilter::safeSQL()
   *
   * @param mixed $source
   * @param mixed $connection
   * @return
   */
    function safeSQL( $source, &$connection )
    {
        if ( is_array($source) )
        {
            foreach ( $source as $key => $value )
                if ( is_string($value) )
                    $source[$key] = $this->quoteSmart( $this->decode($value), $connection ) ;
            return $source ;
        }
        else
            if ( is_string($source) )
            {
                if ( is_string($source) )
                    return $this->quoteSmart( $this->decode($source), $connection ) ;
            }
            else
                return $source ;
    }
  /**
   * InputFilter::quoteSmart()
   *
   * @param mixed $source
   * @param mixed $connection
   * @return
   */
    function quoteSmart( $source, &$connection )
    {
        if ( get_magic_quotes_gpc() )
            $source = stripslashes( $source ) ;
        $source = $this->escapeString( $source, $connection ) ;
        return $source ;
    }
  /**
   * InputFilter::escapeString()
   *
   * @param mixed $string
   * @param mixed $connection
   * @return
   */
    function escapeString( $string, &$connection )
    {
        if ( version_compare(phpversion(), "4.3.0", "<") )
            mysql_escape_string( $string ) ;
        else
            mysql_real_escape_string( $string ) ;
        return $string ;
    }
}
?>
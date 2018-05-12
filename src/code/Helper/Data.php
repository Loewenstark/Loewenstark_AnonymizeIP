<?php

class Loewenstark_AnonymizeIP_Helper_Data
extends Mage_Core_Helper_Abstract
{
    public function convertIp($ip)
    {
        if (is_null($ip))
        {
            return null;
        }
        if (strstr($ip, ':'))
        {
            return $this->_convertIpv6($ip);
        }
        return $this->_convertIpv4($ip);
    }
    
    /**
     * 
     * @param string $ip
     * @return string
     */
    protected function _convertIpv4($ip)
    {
        $split = (array) explode('.', $ip);
        array_pop($split);
        return implode('.', $split).'.0';
    }
    
    protected function _convertIpv6($ip)
    {
        $split = (array) explode(':', $ip);
        array_pop($split);
        array_pop($split);
        return trim(implode(':', $split), ':').'::';
    }
    
}
<?php

class Loewenstark_AnonymizeIP_Model_Cron
extends Mage_Core_Model_Abstract
{
    
    public function run()
    {
        $this->_runOrders();
        $this->_runQuote();
        $this->_runLogVisitorInfo();
    }

    protected function _runOrders()
    {
        $collection = Mage::getModel('sales/order')->getCollection()
                ->addFieldToSelect(array('remote_ip', 'x_forwarded_for'))
                ->addFieldToFilter('created_at', array('lteq' => $this->_get14Days()))
        ;
        $this->_addDefaultFilter($collection, 'remote_ip');
        foreach ($collection as $_order)
        {
            /* @var $_order Mage_Sales_Model_Order */
            $remote_ip = Mage::helper('lanonymizeip')->convertIp($_order->getRemoteIp());
            $_order->setData('remote_ip', $remote_ip);
            $_order->getResource()
                    ->saveAttribute($_order, 'remote_ip');
            
            if (!is_null($_order->getXForwardedFor()) && strlen($_order->getXForwardedFor()) > 2)
            {
                $x_forwarded_for = Mage::helper('lanonymizeip')->convertIp($_order->getXForwardedFor());
                $_order->setData('x_forwarded_for', $x_forwarded_for);
                $_order->getResource()
                        ->saveAttribute($_order, 'x_forwarded_for');
            }
        }
    }

    protected function _runQuote()
    {
        $collection = Mage::getModel('sales/quote')->getCollection()
                ->addFieldToSelect(array('remote_ip'))
                ->addFieldToFilter('created_at', array('lteq' => $this->_get14Days()))
        ;
        $this->_addDefaultFilter($collection, 'remote_ip');
        foreach ($collection as $_quote)
        {
            /* @var $_quote Mage_Sales_Model_Quote */
            $remote_ip = Mage::helper('lanonymizeip')->convertIp($_quote->getRemoteIp());
            $_quote->setData('remote_ip', $remote_ip);
            $_quote->save();
        }
    }
    
    protected function _runLogVisitorInfo()
    {
        $logVisitor = Mage::getModel('log/visitor_online')
                ->getCollection()
                ->addFieldToFilter('first_visit_at', array('lteq' => $this->_get14Days()))
        ;
        /* @var $logVisitor Mage_Log_Model_Visitor */
        foreach ($logVisitor as $_log)
        {
            /* @var $_log Mage_Log_Model_Visitor */
            $ip = Mage::helper('lanonymizeip')->convertIp(inet_ntop($_log->getRemoteAddr()));
            $_log->setData('remote_addr', inet_pton($ip));
            $_log->save();
        }
    }
    
    protected function _get14Days()
    {
        $time = time() - (60*60*24*14); // 14 days
        return date('Y-m-d H:i:s', $time);
    }


    /**
     * 
     * @param type $collection
     * @param type $field
     * @return type
     */
    protected function _addDefaultFilter($collection, $field = 'remote_ip')
    {
        return $collection
                ->addFieldToFilter($field, array('neq' => '127.0.0.1'))
                ->addFieldToFilter($field, array('notnull' => true))
                ->addFieldToFilter($field, array('nlike' => '%.0'))
                ->addFieldToFilter($field, array('nlike' => '%::'))
        ;
    }
}
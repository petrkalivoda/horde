<?php
/**
 * Operator_Driver:: defines an API for implementing storage backends for
 * Operator.
 *
 * $Horde: incubator/operator/lib/Driver.php,v 1.4 2008/07/06 18:21:50 bklang Exp $
 *
 * Copyright 2007-2008 The Horde Project <http://www.horde.org/>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Your Name <you@example.com>
 * @package Operator
 */
class Operator_Driver {

    /**
     * Search the database for call detail records, taking permissions into
     * consideration.
     *
     * @return boolean|PEAR_Error  True on success, PEAR_Error on failure.
     */
    function getRecords($start, $end, $accountcode = null, $dcontext = null,
                         $rowstart = 0, $rowlimit = 100)
    {
        if (empty($accountcode) || $accountcode == '%') {
            $permentry = 'operator:accountcodes';
        } else {
            $permentry = 'operator:accountcodes:' . $accountcode;
        }
        if (!Auth::isAdmin() &&
            !$GLOBALS['perms']->hasPermission($permentry, Auth::getAuth(),
                                              PERMS_READ)) {
            return PEAR::raiseError(_("You do not have permission to view call detail records for that account code."));
        }

        return $this->_getRecords($start, $end, $accountcode, $dcontext,
                                  $rowstart, $rowlimit);
    }

    /**
     * Get summary call statistics per-month for a given time range, account and
     * destination.
     *
     * @param Horde_Date startdate  Start of the statistics window
     * @param Horde_Date enddate    End of the statistics window
     * @param string accountcode    Name of the accont for statistics.  Defaults
     *                              to null meaning all accounts.
     * @param string dcontext       Destination of calls.  Defaults to null.
     *
     *
     * @return array|PEAR_Error     Array of call statistics.  The key of each
     *                              element is the month name in date('Y-m')
     *                              format and the value being an array of
     *                              statistics for calls placed that month. This
     *                              method will additionall return PEAR_Error
     *                              on failure.
     */
    function getMonthlyCallStats($start, $end, $accountcode = null,
                                 $dcontext = null){
        if (empty($accountcode) || $accountcode == '%') {
            $permentry = 'operator:accountcodes';
        } else {
            $permentry = 'operator:accountcodes:' . $accountcode;
        }
        if (!Auth::isAdmin() &&
            !$GLOBALS['perms']->hasPermission($permentry, Auth::getAuth(), 
                                              PERMS_READ)) {
            return PEAR::raiseError(_("You do not have permission to view call detail records for that account code."));
        }

        return $this->_getMonthlyCallStats($start, $end, $accountcode,
                                           $dcontext);
    }

    /**
     * Attempts to return a concrete Operator_Driver instance based on $driver.
     *
     * @param string $driver  The type of the concrete Operator_Driver subclass
     *                        to return.  The class name is based on the
     *                        storage driver ($driver).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Operator_Driver  The newly created concrete Operator_Driver
     *                          instance, or false on an error.
     */
    function factory($driver = null, $params = null)
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = basename($driver);

        if (is_null($params)) {
            // Since we have more than one backend that uses SQL make sure
            // all of them have a chance to inherit the site-wide config.
            $sqldrivers = array('sql', 'asterisksql');
            if (in_array($driver, $sqldrivers)) {
                $params = Horde::getDriverConfig('storage', 'sql');
            } else {
                $params = Horde::getDriverConfig('storage', $driver);
            }
        }

        $class = 'Operator_Driver_' . $driver;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/Driver/' . $driver . '.php';
        }
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return false;
        }
    }

}

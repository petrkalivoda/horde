<?php
/**
 * Login system task for automated upgrade tasks.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */
class Horde_LoginTasks_SystemTask_Upgrade extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_versions = array(
        '4.0',
        '4.0.12'
    );

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '4.0':
            $this->_clearCache();
            $this->_upgradePortal();
            $this->_upgradePrefs();
            break;

        case '4.0.12':
            $this->_replaceWeatherBlock();
        }
    }

    /**
     * Clear the existing cache.
     */
    protected function _clearCache()
    {
        try {
            $GLOBALS['injector']->getInstance('Horde_Cache')->clear();
        } catch (Exception $e) {}
    }

    /**
     * Upgrade portal preferences.
     */
    protected function _upgradePortal()
    {
        $bu = new Horde_Core_Block_Upgrade();
        $bu->upgrade('portal_layout');
    }

    /**
     * Upgrade to the new preferences storage format.
     */
    protected function _upgradePrefs()
    {
        $upgrade_prefs = array(
            'identities'
        );

        $GLOBALS['injector']->getInstance('Horde_Core_Prefs_Storage_Upgrade')->upgradeSerialized($GLOBALS['prefs'], $upgrade_prefs);
    }

    protected function _replaceWeatherBlock()
    {
        $layout = unserialize($GLOBALS['prefs']->getValue('portal_layout'));
        foreach ($layout as $r => $cur_row) {
            foreach ($cur_row as $c => &$cur_col) {
                if (isset($cur_col['app']) &&
                    $cur_col['app'] == 'horde' &&
                    is_array($cur_col['params']) &&
                    Horde_String::lower($cur_col['params']['type2']) == 'horde_block_weatherdotcom') {
                        $col = $GLOBALS['injector']
                            ->getInstance('Horde_Core_Factory_BlockCollection')
                            ->create(array('horde'));
                        $m = $col->getLayoutManager();
                        $m->removeBlock($r, $c);
                }
            }
        }
    }

}

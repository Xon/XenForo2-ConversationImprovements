<?php

/*
 * This file is part of a XenForo add-on.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SV\ConversationImprovements;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

/**
 * Handles installation, upgrades, and uninstallation of the add-on.
 */
class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    /**
     * Alters core tables.
     */
    public function installStep1()
    {
        $sm = $this->schemaManager();
        foreach ($this->getAlters() as $table => $schema) {
            $sm->alterTable($table, $schema);
        }
    }

    /**
     * Applies default permissions for a fresh install.
     */
    public function installStep2()
    {
        $this->applyDefaultPermissions();
    }

    /**
     * @param int   $previousVersion
     * @param array $stateChanges
     */
    public function postUpgrade($previousVersion, array &$stateChanges)
    {
        if ($this->applyDefaultPermissions($previousVersion)) {
            $this->app->jobManager()->enqueueUnique(
                'permissionRebuild',
                'XF:PermissionRebuild',
                [],
                false
            );
        }
    }

    /**
     * Reverses alterations to core tables.
     */
    public function uninstallStep1()
    {
        $sm = $this->schemaManager();
        foreach ($this->getReverseAlters() as $table => $schema) {
            $sm->alterTable($table, $schema);
        }
    }

    /**
     * Cleans up orphaned data.
     */
    public function uninstallStep2()
    {
        $db = $this->db();
        $db->delete(
            'xf_edit_history',
            'content_type = ? OR content_type = ?',
            ['conversation', 'conversation_message']
        );
    }

    /**
     * @return array
     */
    protected function getAlters()
    {
        $alters = [];

        $alters['xf_conversation_message'] = function (Alter $table) {
            /** @var Create|Alter $table */
            $this->addOrChangeColumn($table, 'last_edit_date', 'int')->setDefault(0);
            $this->addOrChangeColumn($table, 'last_edit_user_id', 'int')->setDefault(0);
            $this->addOrChangeColumn($table, 'edit_count', 'int')->setDefault(0);
        };

        return $alters;
    }

    /**
     * @return array
     */
    protected function getReverseAlters()
    {
        $alters = [];

        $alters['xf_conversation_message'] = function (Alter $table) {
            $table->dropColumns([
                'last_edit_date',
                'last_edit_user_id',
                'edit_count'
            ]);
        };

        return $alters;
    }

    /**
     * @param Create|Alter $table
     * @param string       $name
     * @param string|null  $type
     * @param string|null  $length
     *
     * @return \XF\Db\Schema\Column
     *
     * @throws \LogicException If table is unknown schema object
     */
    protected function addOrChangeColumn(
        $table,
        $name,
        $type = null,
        $length = null
    ) {
        if ($table instanceof Create) {
            /** @var Create $table */
            $table->checkExists(true);
            return $table->addColumn($name, $type, $length);
        } elseif ($table instanceof Alter) {
            /** @var Alter $table */
            if ($table->getColumnDefinition($name)) {
                return $table->changeColumn($name, $type, $length);
            }

            return $table->addColumn($name, $type, $length);
        }

        throw new \LogicException(
            "Unknown schema DDL type " . get_class($table)
        );
    }

    /**
     * @param int|null $previousVersion
     *
     * @return bool
     */
    protected function applyDefaultPermissions($previousVersion = null)
    {
        $applied = false;

        if (!$previousVersion) {
            // TODO: default permissions
            // can reply if can start
            // reply limit -1 if can start
            // can manage if can edit any
        }

        return $applied;
    }
}

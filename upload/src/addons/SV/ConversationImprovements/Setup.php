<?php

namespace SV\ConversationImprovements;

use SV\Utils\InstallerHelper;
use SV\Utils\InstallerSoftRequire;
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
    // from https://github.com/Xon/XenForo2-Utils cloned to src/addons/SV/Utils
    use InstallerHelper;
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    /**
     * Alters core tables.
     */
    public function installStep1()
    {
        $sm = $this->schemaManager();
        foreach ($this->getLegacyAlters() as $table => $schema)
        {
            $sm->alterTable($table, $schema);
        }
        foreach ($this->getAlters() as $table => $schema)
        {
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

    public function upgrade2000300Step1()
    {
        $this->installStep1();
    }

    /**
     * @param int   $previousVersion
     * @param array $stateChanges
     */
    public function postUpgrade($previousVersion, array &$stateChanges)
    {
        $this->applyDefaultPermissions($previousVersion);
    }

    /**
     * Reverses alterations to core tables.
     */
    public function uninstallStep1()
    {
        $sm = $this->schemaManager();
        foreach ($this->getReverseAlters() as $table => $schema)
        {
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
    protected function getLegacyAlters()
    {
        // addOrChangeColumn behaves poorly with renameColumn :(

        $alters = [];

        $alters['xf_conversation_message'] = function (Alter $table) {
            $table->dropColumns(['_likes', '_like_users']);
        };

        $alters['xf_conversation_master'] = function (Alter $table) {
            if ($table->getColumnDefinition('last_edit_date'))
            {
                $table->dropColumns('conversation_last_edit_date');
            }
            else if ($table->getColumnDefinition('conversation_last_edit_date'))
            {
                $table->renameColumn('conversation_last_edit_date', 'last_edit_date');
            }

            if ($table->getColumnDefinition('last_edit_user_id'))
            {
                $table->dropColumns('conversation_last_edit_user_id');
            }
            else if ($table->getColumnDefinition('conversation_last_edit_user_id'))
            {
                $table->renameColumn('conversation_last_edit_user_id', 'last_edit_user_id');
            }

            if ($table->getColumnDefinition('edit_count'))
            {
                $table->dropColumns('conversation_edit_count');
            }
            else if ($table->getColumnDefinition('conversation_edit_count'))
            {
                $table->renameColumn('conversation_edit_count', 'edit_count');
            }
        };

        return $alters;
    }

    /**
     * @return array
     */
    protected function getAlters()
    {
        $alters = [];

        $alters['xf_conversation_message'] = function (Alter $table) {
            $this->addOrChangeColumn($table, 'last_edit_date', 'int')->setDefault(0);
            $this->addOrChangeColumn($table, 'last_edit_user_id', 'int')->setDefault(0);
            $this->addOrChangeColumn($table, 'edit_count', 'int')->setDefault(0);
        };

        $alters['xf_conversation_master'] = function (Alter $table) {
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

        $alters['xf_conversation_master'] = function (Alter $table) {
            $table->dropColumns([
                'last_edit_date',
                'last_edit_user_id',
                'edit_count'
            ]);
        };

        return $alters;
    }

    /**
     * @param int|null $previousVersion
     * @return bool
     */
    protected function applyDefaultPermissions($previousVersion = null)
    {
        $applied = false;

        if (!$previousVersion || $previousVersion < 1020003)
        {
            $this->applyGlobalPermission(
                'conversation',
                'canReply',
                'conversation',
                'start'
            );
            $this->applyGlobalPermissionInt(
                'conversation',
                'replyLimit',
                -1,
                'conversation',
                'start'
            );
            $this->applyGlobalPermission(
                'conversation',
                'sv_manageConversation',
                'conversation',
                'editAnyMessage'
            );
        }

        return $applied;
    }

    use InstallerSoftRequire;
    /**
     * @param array $errors
     * @param array $warnings
     */
    public function checkRequirements(&$errors = [], &$warnings = [])
    {
        $this->checkSoftRequires($errors,$warnings);
    }
}

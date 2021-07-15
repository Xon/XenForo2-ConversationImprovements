<?php

namespace SV\ConversationImprovements;

use SV\StandardLib\InstallerHelper;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;

/**
 * Handles installation, upgrades, and uninstallation of the add-on.
 */
class Setup extends AbstractSetup
{
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
            if ($sm->tableExists($table))
            {
                $sm->alterTable($table, $schema);
            }
        }
        foreach ($this->getAlters() as $table => $schema)
        {
            if ($sm->tableExists($table))
            {
                $sm->alterTable($table, $schema);
            }
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

    public function upgrade2010200Step1()
    {
        $this->db()->query('
            DELETE `editHistory`
            FROM xf_edit_history as editHistory
            LEFT JOIN xf_conversation_master on editHistory.content_id = xf_conversation_master.conversation_id
            WHERE editHistory.content_type = \'conversation\' and xf_conversation_master.conversation_id is null
        ');
    }

    public function upgrade2010200Step2()
    {
        $this->db()->query('
            DELETE `editHistory`
            FROM xf_edit_history as editHistory
            LEFT JOIN xf_conversation_message on editHistory.content_id = xf_conversation_message.message_id
            WHERE editHistory.content_type = \'conversation_message\' and xf_conversation_message.message_id is null
        ');
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
            if ($sm->tableExists($table))
            {
                $sm->alterTable($table, $schema);
            }
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

    protected function getLegacyAlters(): array
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

    protected function getAlters(): array
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

    protected function getReverseAlters(): array
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

    protected function applyDefaultPermissions(int $previousVersion = null): bool
    {
        $applied = false;

        if (!$previousVersion || $previousVersion < 1020003)
        {
            $this->applyGlobalPermission('conversation', 'canReply', 'conversation', 'start');
            $this->applyGlobalPermissionInt('conversation', 'replyLimit', -1, 'conversation', 'start');
            $this->applyGlobalPermission('conversation', 'sv_manageConversation', 'conversation', 'editAnyMessage');
        }

        return $applied;
    }
}

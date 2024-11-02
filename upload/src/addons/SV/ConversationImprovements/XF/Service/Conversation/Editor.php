<?php

namespace SV\ConversationImprovements\XF\Service\Conversation;

use SV\ConversationImprovements\XF\Entity\ConversationMaster as ExtendedConversationMasterEntity;
use XF\Entity\ConversationMaster as ConversationMasterEntity;
use XF\Repository\EditHistory as EditHistoryRepo;

/**
 * @extends \XF\Service\Conversation\Editor
 */
class Editor extends XFCP_Editor
{
    /**
     * @var ?string
     */
    protected $oldTitle;

    /**
     * @var ?int
     */
    protected $logDelay;

    /**
     * @var bool
     */
    protected $logEdit = true;

    /**
     * @var bool
     */
    protected $logHistory = true;

    public function logDelay(?int $logDelay): void
    {
        $this->logDelay = $logDelay;
    }

    public function logEdit(bool $logEdit): void
    {
        $this->logEdit = $logEdit;
    }

    public function logHistory(bool $logHistory): void
    {
        $this->logHistory = $logHistory;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        /** @var ExtendedConversationMasterEntity $conversation */
        $conversation = $this->conversation;

        $setupHistory = !$conversation->isChanged('title');
        $oldTitle = $conversation->title_;

        parent::setTitle($title);

        if ($setupHistory && $conversation->isChanged('title'))
        {
            $this->setupEditHistory($oldTitle);
        }
    }

    protected function setupEditHistory(string $oldTitle): void
    {
        /** @var ExtendedConversationMasterEntity $conversation */
        $conversation = $this->conversation;

        $conversation->edit_count++;

        $options = $this->app->options();
        if ($options->editLogDisplay['enabled'] && $this->logEdit)
        {
            $delay = $this->logDelay ?? ($options->editLogDisplay['delay'] * 60);
            if (($conversation->start_date + $delay) <= \XF::$time)
            {
                $conversation->last_edit_date = \XF::$time;
                $conversation->last_edit_user_id = \XF::visitor()->user_id;
            }
        }

        if ($options->editHistory['enabled'] && $this->logHistory)
        {
            $this->oldTitle = $oldTitle;
        }
    }

    /**
     * @return ConversationMasterEntity
     * @noinspection PhpMissingReturnTypeInspection
     */
    protected function _save()
    {
        $visitor = \XF::visitor();
        $db = $this->db();
        $db->beginTransaction();

        $conversation = parent::_save();

        if ($this->oldTitle)
        {
            /** @var EditHistoryRepo $repo */
            $repo = $this->repository('XF:EditHistory');
            $repo->insertEditHistory('conversation', $conversation, $visitor, $this->oldTitle, $this->app->request()->getIp());
        }

        $db->commit();

        return $conversation;
    }
}

<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpMissingParamTypeInspection
 */

namespace SV\ConversationImprovements\XF\Service\Conversation;

/**
 * Extends \XF\Service\Conversation\Editor
 */
class Editor extends XFCP_Editor
{
    /**
     * @var string
     */
    protected $oldTitle;

    /**
     * @var int
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

    /**
     * @param int $logDelay
     */
    public function logDelay($logDelay)
    {
        $this->logDelay = $logDelay;
    }

    /**
     * @param bool $logEdit
     */
    public function logEdit($logEdit)
    {
        $this->logEdit = $logEdit;
    }

    /**
     * @param bool $logHistory
     */
    public function logHistory($logHistory)
    {
        $this->logHistory = $logHistory;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMaster $conversation */
        $conversation = $this->conversation;

        $setupHistory = !$conversation->isChanged('title');
        $oldTitle = $conversation->title_;

        parent::setTitle($title);

        if ($setupHistory && $conversation->isChanged('title'))
        {
            $this->setupEditHistory($oldTitle);
        }
    }

    /**
     * @param string $oldTitle
     */
    protected function setupEditHistory($oldTitle)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMaster $conversation */
        $conversation = $this->conversation;

        $conversation->edit_count++;

        $options = $this->app->options();
        if ($options->editLogDisplay['enabled'] && $this->logEdit)
        {
            $delay = $this->logDelay === null
                ? ($options->editLogDisplay['delay'] * 60)
                : $this->logDelay;
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
     * @return \XF\Entity\ConversationMaster
     */
    protected function _save()
    {
        $visitor = \XF::visitor();
        $db = $this->db();
        $db->beginTransaction();

        $conversation = parent::_save();

        if ($this->oldTitle)
        {
            /** @var \XF\Repository\EditHistory $repo */
            $repo = $this->repository('XF:EditHistory');
            $repo->insertEditHistory('conversation', $conversation, $visitor, $this->oldTitle, $this->app->request()->getIp());
        }

        $db->commit();

        return $conversation;
    }
}

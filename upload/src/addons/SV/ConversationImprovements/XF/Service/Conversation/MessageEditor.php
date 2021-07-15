<?php

namespace SV\ConversationImprovements\XF\Service\Conversation;

/**
 * Extends \XF\Service\Conversation\MessageEditor
 */
class MessageEditor extends XFCP_MessageEditor
{
    /**
     * @var string
     */
    protected $oldMessage;

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
     * @param string $message
     * @param bool   $format
     * @return bool
     */
    public function setMessageContent($message, $format = true)
    {
        $setupHistory = !$this->message->isChanged('message');
        $oldMessage = $this->message->message;

        $result = parent::setMessageContent($message, $format);

        if ($setupHistory && $result && $this->message->isChanged('message'))
        {
            $this->setupEditHistory($oldMessage);
        }

        return $result;
    }

    /**
     * @param string $oldMessage
     */
    protected function setupEditHistory($oldMessage)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $message */
        $message = $this->message;

        $message->edit_count++;

        $options = $this->app->options();
        if ($options->editLogDisplay['enabled'] && $this->logEdit)
        {
            $delay = $this->logDelay === null
                ? ($options->editLogDisplay['delay'] * 60)
                : $this->logDelay;
            if (($message->message_date + $delay) <= \XF::$time)
            {
                $message->last_edit_date = \XF::$time;
                $message->last_edit_user_id = \XF::visitor()->user_id;
            }
        }

        if ($options->editHistory['enabled'] && $this->logHistory)
        {
            $this->oldMessage = $oldMessage;
        }
    }

    /**
     * @return \XF\Entity\ConversationMessage
     */
    protected function _save()
    {
        $visitor = \XF::visitor();
        $db = $this->db();
        $db->beginTransaction();

        $message = parent::_save();

        if ($this->oldMessage)
        {
            /** @var \XF\Repository\EditHistory $repo */
            $repo = $this->repository('XF:EditHistory');
            $repo->insertEditHistory('conversation_message', $message, $visitor, $this->oldMessage, $this->app->request()->getIp());
        }

        $db->commit();

        return $message;
    }
}

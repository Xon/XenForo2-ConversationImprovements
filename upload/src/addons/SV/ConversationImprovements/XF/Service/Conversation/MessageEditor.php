<?php

namespace SV\ConversationImprovements\XF\Service\Conversation;

/**
 * Extends \XF\Service\Conversation\MessageEditor
 */
class MessageEditor extends XFCP_MessageEditor
{
    /**
     * @var ?string
     */
    protected $oldMessage;

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
     * @param string $message
     * @param bool   $format
     * @return bool
     * @noinspection PhpMissingReturnTypeInspection
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

    protected function setupEditHistory(string $oldMessage): void
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $message */
        $message = $this->message;

        $message->edit_count++;

        $options = $this->app->options();
        if ($options->editLogDisplay['enabled'] && $this->logEdit)
        {
            $delay = $this->logDelay ?? ($options->editLogDisplay['delay'] * 60);
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
     * @noinspection PhpMissingReturnTypeInspection
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

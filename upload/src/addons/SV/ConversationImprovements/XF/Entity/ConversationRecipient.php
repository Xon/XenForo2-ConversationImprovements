<?php

namespace SV\ConversationImprovements\XF\Entity;

use XF\Behavior\IndexableContainer;

/**
 * @extends \XF\Entity\ConversationRecipient
 *
 * @property-read ConversationMaster $Conversation
 */
class ConversationRecipient extends XFCP_ConversationRecipient
{
    protected function _postSave()
    {
        parent::_postSave();
        $participationChange = $this->isStateChanged('recipient_state', 'active');
        if ($participationChange !== false)
        {
            if ($participationChange === 'enter')
            {
                $this->triggerReindex();
            }
            else if ($participationChange === 'leave')
            {
                $this->triggerReindex();
            }
        }
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->triggerReindex();
    }

    protected function triggerReindex()
    {
        if (\XF::$versionId >= 2030000)
        {
            return;
        }

        $conversation = $this->Conversation;
        if ($conversation === null || !$conversation->hasBehavior('XF:IndexableContainer'))
        {
            return;
        }
        $conversation->clearCache('recipient_user_ids');
        $conversation->clearCache('active_recipient_user_ids');

        /** @var IndexableContainer|null $indexableContainer */
        $indexableContainer = $conversation->getBehavior('XF:IndexableContainer');
        if ($indexableContainer === null)
        {
            return;
        }
        $indexableContainer->triggerReindex();
    }
}
<?php

namespace SV\ConversationImprovements\XF\Service\Conversation;

/**
 * Extends \XF\Service\Conversation\Creator
 */
class Creator extends XFCP_Creator
{
    /**
     * @param string $recipients
     * @param bool   $checkPrivacy
     * @param bool   $triggerErrors
     */
    public function setRecipients(
        $recipients,
        $checkPrivacy = true,
        $triggerErrors = true
    ) {
        $noRecipientsAllowed = $this->app->options()->sv_conversation_with_no_one;
        if (!$recipients && $noRecipientsAllowed) {
            $starter = $this->starter;
            $this->recipients = [$starter->user_id => $starter];
            return;
        }

        parent::setRecipients($recipients, $checkPrivacy, $triggerErrors);
    }
}

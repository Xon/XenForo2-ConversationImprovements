<?php

namespace SV\ConversationImprovements\XF\Service\Conversation;

use SV\ConversationImprovements\Globals;

/**
 * @extends \XF\Service\Conversation\Creator
 */
class Creator extends XFCP_Creator
{
    /**
     * @param string $recipients
     * @param bool   $checkPrivacy
     * @param bool   $triggerErrors
     */
    public function setRecipients($recipients, $checkPrivacy = true, $triggerErrors = true)
    {
        if (\XF::$versionId >= 2030000)
        {
            parent::setRecipients($recipients, $checkPrivacy, $triggerErrors);
            return;
        }

        if (!$recipients)
        {
            $starter = $this->starter;
            $this->recipients = [$starter->user_id => $starter];

            return;
        }

        Globals::$noRecipientsAllowed = true;
        try
        {
            parent::setRecipients($recipients, $checkPrivacy, $triggerErrors);
        }
        finally
        {
            Globals::$noRecipientsAllowed = false;
        }
    }
}

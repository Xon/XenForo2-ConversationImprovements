<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\XF\Repository;

use SV\ConversationImprovements\Globals;

/**
 * Extends \XF\Repository\Conversation
 */
class Conversation extends XFCP_Conversation
{
    public function getValidatedRecipients($recipients, \XF\Entity\User $from, &$error = null, $checkPrivacy = true)
    {
        $newRecipients = parent::getValidatedRecipients($recipients, $from, $error, $checkPrivacy);

        if (!$newRecipients &&
            Globals::$noRecipientsAllowed &&
            $error instanceof \XF\Phrase &&
            $error->getName() === 'you_cannot_start_conversation_with_yourself')
        {

            $newRecipients = [$from->user_id => $from];
            $error = null;
        }

        return $newRecipients;
    }
}
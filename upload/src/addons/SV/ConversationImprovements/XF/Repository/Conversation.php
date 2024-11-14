<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\XF\Repository;

use SV\ConversationImprovements\Globals;
use XF\Entity\User as UserEntity;
use XF\Phrase as Phrase;

/**
 * @extends \XF\Repository\Conversation
 */
class Conversation extends XFCP_Conversation
{
    public function getValidatedRecipients($recipients, UserEntity $from, &$error = null, $checkPrivacy = true)
    {
        $newRecipients = parent::getValidatedRecipients($recipients, $from, $error, $checkPrivacy);

        if (\XF::$versionId < 2030000 &&
            !$newRecipients &&
            Globals::$noRecipientsAllowed &&
            $error instanceof Phrase &&
            $error->getName() === 'you_cannot_start_conversation_with_yourself')
        {

            $newRecipients = [$from->user_id => $from];
            $error = null;
        }

        return $newRecipients;
    }
}
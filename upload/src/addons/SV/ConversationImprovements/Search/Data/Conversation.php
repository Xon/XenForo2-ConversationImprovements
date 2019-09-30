<?php

namespace SV\ConversationImprovements\Search\Data;

use XF\Mvc\Entity\Entity;
use XF\Search\Data\AbstractData;
use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;

/**
 * A search handler for conversations.
 */
class Conversation extends AbstractData
{
    /**
     * @param Entity $entity
     * @return IndexRecord
     */
    public function getIndexData(Entity $entity)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMaster $entity */
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $firstMessage */
        $firstMessage = $entity->FirstMessage;

        return IndexRecord::create('conversation', $entity->conversation_id, [
            'title'         => $entity->title_,
            'message'       => $firstMessage ? $firstMessage->message_ : '',
            'date'          => $entity->start_date,
            'user_id'       => $entity->user_id,
            'discussion_id' => $entity->conversation_id,
            'metadata'      => $this->getMetadata($entity),
        ]);
    }

    /**
     * @param \SV\ConversationImprovements\XF\Entity\ConversationMaster|\XF\Entity\ConversationMaster $conversation
     * @return array
     */
    protected function getMetadata(\XF\Entity\ConversationMaster $conversation)
    {
        $recipients = \array_keys($conversation->recipients);
        $recipients[] = $conversation->user_id;

        return [
            'conversation' => $conversation->conversation_id,
            'recipients'   => \array_unique($recipients),
        ];
    }

    /**
     * @param MetadataStructure $structure
     */
    public function setupMetadataStructure(MetadataStructure $structure)
    {
        $structure->addField('conversation', MetadataStructure::INT);
        $structure->addField('recipients', MetadataStructure::INT);
    }

    /**
     * @param Entity $entity
     * @return int
     */
    public function getResultDate(Entity $entity)
    {
        /** @var \XF\Entity\ConversationMaster $entity */
        return $entity->start_date;
    }

    /**
     * @param Entity $entity
     * @param array  $options
     * @return array
     */
    public function getTemplateData(Entity $entity, array $options = [])
    {
        return [
            'conversation' => $entity,
            'options'      => $options,
        ];
    }

    /**
     * @param bool $forView
     * @return array
     */
    public function getEntityWith($forView = false)
    {
        $with = ['FirstMessage'];

        if ($forView)
        {
            $with[] = 'Starter';

            $visitor = \XF::visitor();
            $with[] = "Users|{$visitor->user_id}";
        }

        return $with;
    }

    /**
     * @return string
     */
    public function getTemplateName()
    {
        return 'public:sv_convimprov_search_result_conversation';
    }

    /**
     * @param Entity $entity
     * @param string $error
     * @return bool
     */
    public function canUseInlineModeration(Entity $entity, &$error = null)
    {
        return true;
    }
}

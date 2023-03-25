<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\Search\Data;

use SV\SearchImprovements\Search\DiscussionTrait;
use XF\Mvc\Entity\Entity;
use XF\Search\Data\AbstractData;
use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;

/**
 * A search handler for conversations.
 */
class Conversation extends AbstractData
{
    protected static $svDiscussionEntity = \XF\Entity\ConversationMaster::class;
    use DiscussionTrait;

    /**
     * @param Entity $entity
     * @return IndexRecord
     */
    public function getIndexData(Entity $entity)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMaster $entity */
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
        $metaData = [
            'conversation' => $conversation->conversation_id,
            'recipients'   => $conversation->getSearchableRecipients(),
        ];

        $this->populateDiscussionMetaData($conversation, $metaData);

        return $metaData;
    }

    /**
     * @param MetadataStructure $structure
     */
    public function setupMetadataStructure(MetadataStructure $structure)
    {
        $structure->addField('conversation', MetadataStructure::INT);
        $structure->addField('recipients', MetadataStructure::INT);

        $this->setupDiscussionMetadataStructure($structure);
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
            $with[] = 'Users|' . $visitor->user_id;
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
     * @param \XF\Phrase|string|null $error
     * @return bool
     */
    public function canUseInlineModeration(Entity $entity, &$error = null)
    {
        return true;
    }
}

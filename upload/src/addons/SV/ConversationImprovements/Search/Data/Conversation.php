<?php
/**
 * @noinspection PhpMissingParentCallCommonInspection
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\Search\Data;

use SV\ConversationImprovements\XF\Entity\ConversationMaster as ExtendedConversationMasterEntity;
use SV\SearchImprovements\Search\DiscussionTrait;
use XF\Entity\ConversationMaster as ConversationMasterEntity;
use XF\Mvc\Entity\Entity;
use XF\Phrase;
use XF\Search\Data\AbstractData;
use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;

/**
 * XF2.1/XF2.2
 * A search handler for conversations.
 */
class Conversation extends AbstractData
{
    protected static $svDiscussionEntity = ConversationMasterEntity::class;
    use DiscussionTrait;

    /**
     * @param Entity $entity
     * @return IndexRecord
     */
    public function getIndexData(Entity $entity)
    {
        /** @var ExtendedConversationMasterEntity $entity */
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
     * @param ExtendedConversationMasterEntity|ConversationMasterEntity $conversation
     * @return array
     */
    protected function getMetadata(ConversationMasterEntity $conversation)
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
        /** @var ConversationMasterEntity $entity */
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
     * @param Phrase|string|null $error
     * @return bool
     */
    public function canUseInlineModeration(Entity $entity, &$error = null)
    {
        return true;
    }
}

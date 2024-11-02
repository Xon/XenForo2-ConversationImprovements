<?php /** @noinspection PhpMissingReturnTypeInspection */

namespace SV\ConversationImprovements\XF\Search\Data;

use SV\SearchImprovements\Search\DiscussionTrait;
use XF\Entity\ConversationMaster as ConversationMasterEntity;
use XF\Search\MetadataStructure;

/**
 * XF2.3+ support
 * @extends \XF\Search\Data\Conversation
 */
class Conversation extends XFCP_Conversation
{
    protected static $svDiscussionEntity = ConversationMasterEntity::class;
    use DiscussionTrait;

    protected function getMetaData(ConversationMasterEntity $entity): array
    {
        $metaData = parent::getMetaData($entity);

        $this->populateDiscussionMetaData($entity, $metaData);

        return $metaData;
    }

    public function setupMetadataStructure(MetadataStructure $structure): void
    {
        parent::setupMetadataStructure($structure);

        $this->setupDiscussionMetadataStructure($structure);
    }
}

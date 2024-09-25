<?php /** @noinspection PhpMissingReturnTypeInspection */

namespace SV\ConversationImprovements\XF\Search\Data;

use SV\SearchImprovements\Search\DiscussionTrait;
use XF\Search\MetadataStructure;

/**
 * XF2.3+ support
 * @extends \XF\Search\Data\Conversation
 */
class Conversation extends XFCP_Conversation
{
    protected static $svDiscussionEntity = \XF\Entity\ConversationMaster::class;
    use DiscussionTrait;

    protected function getMetaData(\XF\Entity\ConversationMaster $entity): array
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

    /**
     * @return string
     */
    public function getTemplateName()
    {
        return 'public:sv_convimprov_search_result_conversation';
    }
}

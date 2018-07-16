# Conversation Improvements

A collection of improvements to the XenForo Conversation system.

Features:
- Adds conversation search, with options to search by recipient.
- New Conversation Permissions
- Conversation Message Edit history
- Conversation Title Edit history
- Allow conversations with no-one.

Note; for forums with a large number of conversations see the Installing section!

### Adds conversation search, with options to search by recipient

Users must be a member of the conversation to see the conversation in search results.

Does not permit moderators/administrators to see another person's conversations in search results.

Due to XenForo's design, this addon impacts general 'everything' search as per search handler constrains are not invoked resulting in false positives which are removed by XenForo rather than the search subsystem.

Adds each conversation, and conversation message to the XenForo Search store (MySQL or Elastic Search), which may result in a larger search index.

### New Conversation Permissions

Just takes away a user's "reply" button, no banners.

The reply limit is for the entire conversation, but the limit is per user group. Consider when User A & User B are members of a conversation.

User A can have a reply limit of 5.
User B can have a reply limit of 10.

Once the conversation has >5 replies, User A can no longer post.
Once the conversation has >10 replies, User A and User B can no longer post.

### Conversation Message Edit History

Adds edit history for conversation messages.

### Conversation Title Edit History

Adds edit history for conversation's title, and implements a new moderator permission "Manage Conversations by anyone" to allow non-conversation starters to edit a conversation.

# Known Issues
- Does not respect/implement any silent editing window.

#Installing for large forums

For large forums, please try manually adding all the columns in a single step. 
This took upto 5 minutes for 1.3 million conversation messages (compressed).

```
ALTER TABLE `xf_conversation_message` 
    ADD COLUMN `edit_count` int not null default 0,
    ADD COLUMN `last_edit_date` int not null default 0,
    ADD COLUMN `last_edit_user_id` int not null default 0;
ALTER TABLE `xf_conversation_master` 
  ADD COLUMN `conversation_edit_count` int not null default 0,
  ADD COLUMN `conversation_last_edit_date` int not null default 0,
  ADD COLUMN `conversation_last_edit_user_id` int not null default 0;
```

#Permissions

- Can Reply to Conversation.
- Reply Limit for Conversation.
- Manage Conversations by anyone.

#Manual post-installation steps

The add-on will report conversation related content types that require re-indexing.

#Performance impact

- 1 extra query per conversation message posted due to indexing, and indexing itself.

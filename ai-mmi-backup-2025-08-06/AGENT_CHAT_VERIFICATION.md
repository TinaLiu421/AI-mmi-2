# Agent Chat Feature - Verification Guide

## Overview
The agent-user chat system has been fully implemented and is ready for testing. Users can contact agents through the portal and agents can receive and reply to messages.

## Implementation Summary

### ✅ Components Completed

1. **Database Schema** (`app_agent_chat_messages` table)
   - `id`: Primary key
   - `sender_member_id`: Member sending the message (user or agent)
   - `sender_guest_id`: Guest sending the message (unauthenticated users)
   - `receiver_member_id`: Member receiving the message
   - `receiver_guest_id`: Guest receiving the message
   - `message`: Message content
   - `created_at`, `updated_at`: Timestamps
   - Indexes for conversation lookups

2. **Backend Controller** (`app/Http/Controllers/Web/Agent_chat.php`)
   - `index($targetId)`: Display chat interface
   - `send()`: Accept and store messages (POST endpoint)
   - `messages()`: Retrieve chat history (GET endpoint)
   - `threads()`: List active conversations for agents (GET endpoint)
   - Local environment bypass for token validation during development

3. **Routes** (`routes/web.php`)
   - `GET /agent_chat` → Display main chat page
   - `GET /agent_chat/{targetId}` → Display chat with specific target
   - `GET /agent_chat/messages/{targetType}/{targetId}` → Fetch messages
   - `GET /agent_chat/threads` → Get agent inbox threads
   - `POST /agent_chat/send` → Send a message

4. **Frontend UI** (`resources/views/web/agent_chat.blade.php`)
   - Sidebar: Lists agents (for users) or conversation threads (for agents)
   - Main panel: Message display with auto-scrolling
   - Input form: Send messages with form submission handling

5. **Client-Side Logic** (`public/asset/js/web/agent_chat.js`)
   - Auto-select first conversation on page load
   - Real-time message polling (3-second intervals)
   - Error handling that preserves user input on send failures
   - CSRF token handling
   - iToken generation for security (bypassed in local env)

6. **Styling** (`public/asset/css/web/agent_chat.css`)
   - Responsive chat UI with sidebar and main panel
   - Message bubbles (mine/theirs styling)
   - Scroll area for messages
   - Form styling

7. **Integration** (Navigation Links)
   - "Contact An Agent" button on Study page
   - "Contact An Agent" button on Migration page
   - Links to `/agent_chat` endpoint

## How to Test

### 1. User Sends Message to Agent

1. Login as user: `testuser@example.com` / `Test1234`
2. Navigate to `/agent_chat`
3. Select an agent from the sidebar (e.g., "admin@wealthskey.com")
4. Type a message in the input field
5. Click "Send"
6. Verify message appears in the chat window (in your bubble on the right)
7. Check database: `SELECT * FROM app_agent_chat_messages ORDER BY id DESC LIMIT 1;`

### 2. Agent Receives and Views Message

1. Login as agent: `admin@wealthskey.com` / `Test1234` (in a separate browser/incognito window)
2. Navigate to `/agent_chat`
3. Verify the user appears in the sidebar under "Agent Inbox" with preview of message
4. Click on the conversation to view message history
5. Verify the user's message appears (in their bubble on the left)

### 3. Agent Replies to User

1. While logged as agent, type a reply message
2. Click "Send"
3. Verify reply appears in agent's chat window
4. Check database that sender_member_id = agent's ID (1 or 5)

### 4. User Receives Reply

1. Switch back to user's session
2. The message should auto-load via polling (check within 3 seconds)
3. Verify agent's reply appears in the conversation

## Test Data Available

**Users:**
- Email: `testuser@example.com`
- Password: `Test1234`
- Member ID: 32
- Type: Individual (1)

**Agents:**
- Email: `admin@wealthskey.com`
- Password: `Test1234`
- Member ID: 1
- Type: Service Provider (3)

---

- Email: `info@learnstay.world`
- Password: `Test1234`
- Member ID: 5
- Type: Service Provider (3)

## Key Features

✅ **Member-to-Member Chat**: Full two-way messaging between users and agents
✅ **Guest Support**: Unauthenticated guests can message agents (via guest_id cookie)
✅ **Thread Management**: Agents can view all their conversations in one inbox
✅ **Auto-Selection**: First conversation auto-loads when page opens
✅ **Error Resilience**: Failed sends don't clear the input field
✅ **Real-time Updates**: Messages poll every 3 seconds
✅ **Local Dev Mode**: Bypasses token validation in local environment (APP_ENV=local)
✅ **CSRF Protection**: Includes CSRF token validation
✅ **Security**: iToken validation for production (local bypass for dev)

## Database Verification

```sql
-- Check if table exists
SHOW TABLES LIKE 'agent_chat_messages';

-- View table structure
DESCRIBE agent_chat_messages;

-- Count messages
SELECT COUNT(*) FROM app_agent_chat_messages;

-- View recent messages
SELECT * FROM app_agent_chat_messages ORDER BY created_at DESC LIMIT 10;

-- View specific conversation
SELECT * FROM app_agent_chat_messages 
WHERE (sender_member_id = 32 AND receiver_member_id = 1) 
   OR (sender_member_id = 1 AND receiver_member_id = 32) 
ORDER BY created_at ASC;
```

## Configuration

**Environment:** `.env`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=aimmi_local`
- `APP_ENV=local` (enables token bypass for development)

**Session Configuration:** `config/session.php`
- Lifetime: 120 minutes
- Cookie name: `ai_mmi_session`
- Driver: File-based

## Troubleshooting

**Problem**: Messages not sending
- Check browser console for JavaScript errors
- Verify CSRF token is present in form
- Check that target agent is valid (member type 2 or 3, status > 0)

**Problem**: Agent doesn't see user messages
- Verify agent is logged in and has member type 3
- Check that messages table has sender_member_id = user ID, receiver_member_id = agent ID
- Ensure page refresh or wait for 3-second polling interval

**Problem**: Chat not loading at `/agent_chat`
- Verify routes are registered in `routes/web.php`
- Check that controller class `Agent_chat` extends `WebController`
- Verify authentication (user must be logged in as member)

## File Structure

```
app/Http/Controllers/Web/Agent_chat.php        # Main controller
resources/views/web/agent_chat.blade.php       # UI template
public/asset/js/web/agent_chat.js              # Client-side logic
public/asset/css/web/agent_chat.css            # Styling
database/migrations/2026_02_11_000001_...php   # Table creation
database/migrations/2026_02_11_000002_...php   # Indexes
database/migrations/2026_02_11_100000_...php   # Social columns
routes/web.php                                  # Route definitions
```

## Notes for Production

When deploying to production:

1. Remove `APP_ENV=local` from `.env` (or set to `production`)
2. Token validation will be enforced in the `send()` method
3. Ensure `pageAction()` middleware is functioning correctly
4. Test with real iToken generation (currently bypassed in local mode)
5. Consider rate limiting for message endpoints to prevent spam
6. Add message validation (character limits, profanity filters, etc.)
7. Implement message notifications (email/SMS when agent receives message)

## Completed Date

Feature completed and pushed to branch: `develop-new`
Last commit: "Fix agent chat routes and local environment bypass for threads endpoint"

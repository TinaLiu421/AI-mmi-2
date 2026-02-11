# Agent Chat System - Debugging Guide

## Problem
When signed in as an agent account (e.g., Wealthskey), the agent chat page shows "No conversations yet" instead of displaying conversations with users.

## Solution & Testing Steps

### Step 1: Verify You're Logged In as the Correct Agent

1. **Log Out Completely**
   - Click the logout button or sign out

2. **Clear Cookies** (Important!)
   - Open browser DevTools (F12)
   - Go to Application/Storage tab
   - Delete all cookies for the site
   - Clear localStorage and sessionStorage

3. **Log Back In**
   - Go to the login page: `http://127.0.0.1:8000/en/account_login`
   - Enter credentials:
     - **Email:** `admin@wealthskey.com`
     - **Password:** `Test1234`
   - Wait for redirect to home page

4. **Verify Login Success**
   - You should be redirected to the home page
   - Check the top-right corner - you should see your profile/name
   - If you see "Sign In" button, you're NOT logged in

### Step 2: Access the Agent Chat Page

1. **Go to Agent Chat**
   - Navigate to: `http://127.0.0.1:8000/en/agent_chat`
   - **OR** look for a "Chat" or "Agent" menu option if available

2. **Check What You See**

   **If you see "Agent Inbox (1 conversations)":**
   ✓ SUCCESS - You're logged in as an agent and have conversations
   - Click on the conversation to open it
   - You should see the user's message
   - Type a reply and send it

   **If you see "Choose an Agent (3 agents)" with a list of agents:**
   ✗ PROBLEM - You're NOT logged in as an agent
   - Log out and try again
   - Make sure you're using the correct credentials

   **If you see "Agent Inbox (0 conversations)":**
   ✗ PROBLEM - You're logged in as an agent but no conversations found
   - See Step 3 below

### Step 3: Troubleshoot "No Conversations" Issue

1. **Check Browser Console**
   - Open DevTools (F12)
   - Go to Console tab
   - Look for any errors (red text)
   - Report any errors you see

2. **Check JavaScript Configuration**
   - In DevTools Console, type: `window.agentChatConfig`
   - You should see:
     ```javascript
     {
       isAgent: true,
       activeTargetType: "member",
       activeTargetId: 32,
       ...
     }
     ```
   - If `isAgent: false`, you're not logged in as an agent
   - If `activeTargetId: null`, no conversations were found

3. **Check Server Logs**
   - The server is logging all chat access
   - Look at: `storage/logs/laravel.log`
   - Search for "Agent_chat.index" entries
   - You should see log entries like:
     ```
     [INFO] Agent_chat.index: {"memberId":1,"isAgent":true,"member_email":"admin@wealthskey.com","member_type":2}
     [INFO] Agent_chat.threads_loaded: {"thread_count":1,"threads":[...]}
     ```

### Step 4: Test the Conversation Flow

1. **Send a Test Message as User**
   - In a separate browser tab or window:
     - Log in as a user: `testuser@example.com` / `Test1234`
     - Go to `/en/agent_chat`
     - You should see "Choose an Agent" with agent list
     - Click on "Wealthskey Migration"
     - Type a message: "Test message from user"
     - Click Send

2. **Check as Agent**
   - Switch back to the agent tab
   - Refresh the page: `http://127.0.0.1:8000/en/agent_chat`
   - You should now see "Agent Inbox (X conversations)"
   - Click on the conversation
   - You should see the user's test message

3. **Reply as Agent**
   - Type a reply: "Message from agent"
   - Click Send
   - The message should be saved

4. **Verify User Receives Reply**
   - Switch back to user tab
   - Refresh or wait for auto-refresh
   - You should see the agent's reply

## Expected Behavior

### For Agents (Type 2 or 3, Status > 0)
- View: "Agent Inbox" with conversation list
- Can see all conversations with users
- Can click each conversation to read messages
- Can send replies to users

### For Regular Users (Type 1)
- View: "Choose an Agent" with agent list
- Can select an agent to chat with
- Can send messages to agents
- Can see agent replies in real-time

### For Guests (Not Logged In)
- View: "Choose an Agent" with agent list
- Can select an agent anonymously
- Messages are stored with guest ID
- Agents can see guest messages

## Database Check

If problems persist, run these commands to verify data:

```bash
# Check if agent account exists and is active
mysql -h 127.0.0.1 -u aimmi -p'aimmi123' aimmi_local -e \
  "SELECT id, email, alias_name, type, status FROM app_member WHERE id = 1;"

# Check if agent has access token
mysql -h 127.0.0.1 -u aimmi -p'aimmi123' aimmi_local -e \
  "SELECT * FROM app_member_token WHERE member_id = 1 LIMIT 1;"

# Check conversations for agent 1
mysql -h 127.0.0.1 -u aimmi -p'aimmi123' aimmi_local -e \
  "SELECT id, sender_member_id, receiver_member_id, message FROM app_agent_chat_messages \
   WHERE sender_member_id = 1 OR receiver_member_id = 1 LIMIT 10;"
```

## Still Having Issues?

1. Check the server logs: `tail -50 storage/logs/laravel.log`
2. Look for any PHP errors
3. Make sure the database has data:
   - The agent account should be type 2 or 3
   - The agent should have status = 1
   - There should be messages in `app_agent_chat_messages` table

## Key Points

✓ Agent accounts (type 2 or 3) see "Agent Inbox"
✓ Regular users (type 1) see "Choose an Agent"  
✓ Guests see "Choose an Agent" without login
✓ Conversations require at least one message between agent and user
✓ Agent must be logged in with valid token/cookie
✓ All requests must have valid CSRF token (handled automatically)

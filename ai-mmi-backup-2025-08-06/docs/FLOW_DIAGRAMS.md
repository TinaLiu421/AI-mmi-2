# Conversation Flow System - Visual Diagrams

## Complete System Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                        USER SENDS MESSAGE                            │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     Home Controller                                  │
│  1. Gets member from DB via access token                            │
│  2. Reads primary_plan_code from Member model                       │
│  3. Creates userProfile array                                       │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│              ConversationFlowService::analyzeAndTrigger              │
│  • Receives: question, reply, userProfile                           │
│  • Checks subscription tier                                         │
│  • Runs detection logic                                             │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                ┌────────────┴────────────┐
                │                         │
                ▼                         ▼
      ┌──────────────────┐      ┌──────────────────┐
      │  Promo Triggered │      │   No Trigger     │
      │   Returns Array  │      │  Returns null    │
      └────────┬─────────┘      └────────┬─────────┘
               │                         │
               ▼                         ▼
      ┌──────────────────┐      ┌──────────────────┐
      │  Display Promo   │      │  Normal Response │
      │  + Button(s)     │      │    Only          │
      └──────────────────┘      └──────────────────┘
```

---

## Tier Detection Flow

```
                    ┌─────────────────┐
                    │  User Message   │
                    └────────┬────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │   Get Subscription Tier      │
              │   (primary_plan_code)        │
              └──────┬───────────────────────┘
                     │
        ┌────────────┼────────────┬─────────────┬───────────┐
        │            │            │             │           │
        ▼            ▼            ▼             ▼           ▼
    ┌──────┐   ┌─────────┐  ┌────────┐   ┌─────────┐  ┌──────┐
    │ free │   │ all_ai  │  │ hybrid │   │ premium │  │ vip  │
    └──┬───┘   └────┬────┘  └───┬────┘   └────┬────┘  └───┬──┘
       │            │            │             │           │
       ▼            ▼            ▼             ▼           ▼
    Every 5    Intelligent  Intelligent   Intelligent   No Promo
    Messages   Detection    Detection     Detection     (Top Tier)
    + Study    (Human       (Final        (Full
    Trigger    Consult)     Validate)     Service)
```

---

## Priority Check Order

```
┌───────────────────────────────────────────────────────────────┐
│ 1. POSITIVE RESPONSE DETECTION (All Tiers)                    │
│    • Check if last promo was shown                            │
│    • Check if user response shows explicit upgrade interest   │
│    • If YES → Show upgrade info immediately                   │
│    • If NO → Continue to next check                           │
└────────────────────────────┬──────────────────────────────────┘
                             │
                             ▼
┌───────────────────────────────────────────────────────────────┐
│ 2. INTELLIGENT DETECTION (Paid Tiers: all_ai, hybrid, premium)│
│    • Calculate score based on keywords                        │
│    • Check if score >= 5                                      │
│    • Check cooldown (7 messages)                              │
│    • If all pass → Show tier-specific promo                   │
│    • If not → Continue to next check                          │
└────────────────────────────┬──────────────────────────────────┘
                             │
                             ▼
┌───────────────────────────────────────────────────────────────┐
│ 3. STUDY ASSISTANCE (free, all_ai only)                       │
│    • Check for education keywords                             │
│    • Check for help request keywords                          │
│    • Check cooldown (10 messages)                             │
│    • If all pass → Show study assistance                      │
│    • If not → Continue to next check                          │
└────────────────────────────┬──────────────────────────────────┘
                             │
                             ▼
┌───────────────────────────────────────────────────────────────┐
│ 4. EVERY 5 MESSAGES (free only)                               │
│    • Check if message count % 5 == 0                          │
│    • Check cooldown (3 messages since any trigger)            │
│    • If all pass → Show AI Smart Plan promo                   │
│    • If not → No promo                                        │
└────────────────────────────┬──────────────────────────────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │   Return Result  │
                    └─────────────────┘
```

---

## Scoring Algorithm (Intelligent Detection)

```
┌─────────────────────────────────────────────────────────────────┐
│                    USER QUESTION                                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │  score = 0      │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Check for   │    │  Check for   │    │  Check for   │
│  Uncertainty │    │  Document    │    │  Professional│
│  Keywords    │    │  Review      │    │  Validation  │
│              │    │  Keywords    │    │  Keywords    │
│  +3 points   │    │  +4 points   │    │  +4 points   │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                    │
       └───────────────────┼────────────────────┘
                           │
                           ▼
                  ┌─────────────────┐
                  │  Total Score    │
                  └────────┬────────┘
                           │
                ┌──────────┴──────────┐
                │                     │
                ▼                     ▼
         ┌─────────────┐      ┌─────────────┐
         │ score >= 5  │      │ score < 5   │
         │  ✅ TRIGGER │      │ ❌ NO TRIGGER│
         └─────────────┘      └─────────────┘
```

---

## Cooldown Timeline

```
Message Timeline:
─────────────────────────────────────────────────────────────────►

Msg 5:   ⚡ PROMO SHOWN (All AI → Hybrid)
         └─ Record: last_triggers['hybrid_expert_consultation'] = 5

Msg 6-11: 🚫 COOLDOWN ACTIVE (messages 6,7,8,9,10,11)
          └─ Score may be high, but cooldown blocks trigger

Msg 12:  ✅ COOLDOWN PASSED (12 - 5 = 7 messages)
         └─ If score >= 5, promo can trigger again

         ⚡ PROMO SHOWN AGAIN
         └─ Record: last_triggers['hybrid_expert_consultation'] = 12

Msg 13-18: 🚫 COOLDOWN ACTIVE again

Msg 19:  ✅ COOLDOWN PASSED (19 - 12 = 7)
         └─ Can trigger if score >= 5
```

---

## Positive Response Flow

```
┌─────────────────────────────────────────────────────────────────┐
│  Message N: [PROMO SHOWN] "Upgrade to AI Smart Plan ($79)"     │
│  └─ Records: last_trigger_type = 'upgrade_prompt_free'         │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  Message N+1: User responds                                     │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
                  ┌──────────────────────┐
                  │ isPositiveResponse() │
                  └──────────┬───────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Layer 1:    │    │  Layer 2:    │    │  Layer 3:    │
│  Negative    │    │  Explicit    │    │  Generic     │
│  Detection   │    │  Interest    │    │  Filter      │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                    │
       ▼                   ▼                    ▼
"no thanks"         "tell me more"         "yes"
"not interested"    "how much?"            "okay"
       │                   │                    │
       ▼                   ▼                    ▼
   ❌ FALSE            ✅ TRUE                ❌ FALSE
   (reject)            (interest)             (too generic)
```

### Example Scenarios

**Scenario 1: Explicit Interest** ✅

```
Msg 10: [PROMO] "Upgrade to AI Smart Plan ($79)"
Msg 11: "How much does it cost?"
        └─ Layer 2 detects "how much" ✅
        └─ Result: Show detailed upgrade info
```

**Scenario 2: Generic Response** ❌

```
Msg 10: Chatbot: "Would you like to know about visas?"
Msg 11: [PROMO] "Upgrade to AI Smart Plan"
Msg 12: "Yes" (answering Msg 10, NOT Msg 11)
        └─ Layer 3 detects single-word "yes" ❌
        └─ Result: Ignored, no follow-up promo
```

**Scenario 3: Rejection** ❌

```
Msg 10: [PROMO] "Get Expert Consultation ($199)"
Msg 11: "No thanks, not interested"
        └─ Layer 1 detects "no thanks" ❌
        └─ Result: No follow-up, respect rejection
```

---

## Free Tier Complete Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                        FREE TIER USER                            │
└────────────────────────────┬────────────────────────────────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Every 5     │    │  Study       │    │  Positive    │
│  Messages    │    │  Keywords    │    │  Response    │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                    │
       ▼                   ▼                    ▼
Message 5,10,15...  "Help me apply     "Yes, tell me
                    to university"      more"
       │                   │                    │
       ▼                   ▼                    ▼
   Cooldown:          Cooldown:            Cooldown:
   3 messages         10 messages          None
       │                   │                    │
       ▼                   ▼                    ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│ AI Smart     │    │ Study        │    │ AI Smart     │
│ Plan ($79)   │    │ Assistance   │    │ Plan ($79)   │
│ → /upgrade   │    │ → /apply     │    │ → /upgrade   │
└──────────────┘    └──────────────┘    └──────────────┘
```

---

## Paid Tier Complete Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    PAID TIER USER                                │
│              (all_ai, hybrid, premium)                           │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
                  ┌──────────────────────┐
                  │  User Sends Message  │
                  └──────────┬───────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Positive    │    │  Intelligent │    │  Study       │
│  Response    │    │  Detection   │    │  Assistance  │
│  (Priority)  │    │  (Main)      │    │  (Optional)  │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                    │
       │                   ▼                    │
       │         ┌──────────────────┐           │
       │         │ Calculate Score  │           │
       │         └────────┬─────────┘           │
       │                  │                     │
       │         ┌────────┴────────┐            │
       │         │                 │            │
       │         ▼                 ▼            │
       │    Score >= 5        Score < 5        │
       │         │                 │            │
       │         ▼                 │            │
       │    ┌─────────┐            │            │
       │    │Cooldown?│            │            │
       │    └────┬────┘            │            │
       │         │                 │            │
       │    ┌────┴────┐            │            │
       │    │         │            │            │
       │    ▼         ▼            │            │
       │  Passed   Active          │            │
       │    │         │            │            │
       │    ▼         │            │            │
       │  SHOW        │            │            │
       │  PROMO       │            │            │
       │    │         │            │            │
       └────┴─────────┴────────────┴────────────┘
                      │
                      ▼
              ┌──────────────┐
              │  Next Tier   │
              │  Promo       │
              │  → /upgrade  │
              └──────────────┘
```

---

## Database Integration Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                     DATABASE TABLES                              │
└─────────────────────────────┬───────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
        ▼                     ▼                     ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│    member    │    │subscriptions │    │    plans     │
│              │    │              │    │              │
│ • id         │◄───┤ • member_id  │    │ • id         │
│              │    │ • plan_id    ├───►│ • code       │
│              │    │ • status     │    │ • name       │
│              │    │ • starts_at  │    │ • price_usd  │
│              │    │ • ends_at    │    │              │
└──────────────┘    └──────────────┘    └──────────────┘
        │
        ▼
┌─────────────────────────────────────────────────────────────────┐
│                Member Model (Member.php)                         │
│  • Fetches active subscriptions                                 │
│  • Joins with plans table                                       │
│  • Sets primary_plan_code from plan.code                        │
│  • Returns: 'free', 'all_ai', 'hybrid', 'premium', 'vip'       │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              Home Controller (Home.php)                          │
│  • Gets $this->_current_member from Member model                │
│  • Reads primary_plan_code                                      │
│  • Passes to ConversationFlowService                            │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│         ConversationFlowService (analyzeAndTrigger)              │
│  • Uses subscription_tier to determine flow logic               │
└─────────────────────────────────────────────────────────────────┘
```

---

## Session State Management

```
┌─────────────────────────────────────────────────────────────────┐
│                    SESSION STATE                                 │
│  Stored per member_id in session                                │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │  Structure:     │
                    │  {              │
                    │   prompt_count  │
                    │   last_triggers │
                    │   recent_topics │
                    │  }              │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│prompt_count  │    │last_triggers │    │recent_topics │
│              │    │              │    │              │
│Total promos  │    │Per-type      │    │Topic hashes  │
│shown in      │    │tracking:     │    │of last 5     │
│session       │    │              │    │questions     │
│              │    │• hybrid_exp  │    │              │
│Used for      │    │• premium_con │    │Used for      │
│analytics     │    │• vip_global  │    │detecting     │
│              │    │• upgrade     │    │repeated      │
│              │    │• study       │    │topics        │
└──────────────┘    └──────────────┘    └──────────────┘
```

---

## Error Handling Flow

```
┌─────────────────────────────────────────────────────────────────┐
│              analyzeAndTrigger() called                          │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │  Try Block      │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Success     │    │  Exception   │    │  Null Return │
│              │    │              │    │              │
│Return promo  │    │Log error     │    │No trigger    │
│array         │    │Return null   │    │detected      │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                    │
       └───────────────────┼────────────────────┘
                           │
                           ▼
                ┌──────────────────────┐
                │  Controller Handles  │
                │  • Promo array: show │
                │  • Null: no promo    │
                └──────────────────────┘
```

---

## Complete User Journey Example

```
FREE USER → ALL AI → HYBRID → PREMIUM → VIP

┌─────────────────────────────────────────────────────────────────┐
│ Msg 1-4: Free tier, learning about visas                        │
│          └─ No promo yet                                        │
├─────────────────────────────────────────────────────────────────┤
│ Msg 5:   "What documents do I need?"                            │
│          └─ ⚡ PROMO: AI Smart Plan ($79) → /upgrade           │
├─────────────────────────────────────────────────────────────────┤
│ Msg 6:   User clicks [Upgrade], subscribes to all_ai           │
│          └─ Now subscription_tier = 'all_ai'                   │
├─────────────────────────────────────────────────────────────────┤
│ Msg 10:  "I'm not sure if my documents are correct"            │
│          └─ Score: 7 points (uncertainty + documents)          │
│          └─ ⚡ PROMO: Hybrid Expert Plan ($199) → /upgrade     │
├─────────────────────────────────────────────────────────────────┤
│ Msg 11:  "How much does it cost?"                              │
│          └─ Positive response detected!                        │
│          └─ ⚡ PROMO: Detailed Hybrid info → /upgrade          │
├─────────────────────────────────────────────────────────────────┤
│ Msg 12:  User clicks [Upgrade], subscribes to hybrid           │
│          └─ Now subscription_tier = 'hybrid'                   │
├─────────────────────────────────────────────────────────────────┤
│ Msg 20:  "About to submit. Make sure everything is correct"    │
│          └─ Score: 9 points (submission + validation)          │
│          └─ ⚡ PROMO: Premium Plan ($699) → /upgrade           │
├─────────────────────────────────────────────────────────────────┤
│ Msg 21:  User clicks [Upgrade], subscribes to premium          │
│          └─ Now subscription_tier = 'premium'                  │
├─────────────────────────────────────────────────────────────────┤
│ Msg 30:  "Can you handle everything for me?"                   │
│          └─ Score: 11 points (full service + delegation)       │
│          └─ ⚡ PROMO: VIP Plan ($999) → /upgrade               │
├─────────────────────────────────────────────────────────────────┤
│ Msg 31:  User clicks [Upgrade], subscribes to vip              │
│          └─ Now subscription_tier = 'vip'                      │
├─────────────────────────────────────────────────────────────────┤
│ Msg 40+: VIP user, no more upgrade prompts                     │
│          └─ At top tier, fully supported                       │
└─────────────────────────────────────────────────────────────────┘
```

---

**Documentation Version:** 1.0
**Last Updated:** 2025-01-16

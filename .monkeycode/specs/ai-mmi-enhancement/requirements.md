# Requirements Document

## Introduction

AI-mmi 平台增强计划，包含三大方向：Web/Mobile 响应式增强、AI 定制化训练（Prompt 优化 + 知识库增强 + 微调模型）、以及英语学习 AI 全功能集成。目标是将平台从仅支持移民留学咨询升级为「移民+语言学习一体化 AI 助手」，同时提升移动端用户体验。

## Glossary

- **System**: AI-mmi Web 应用平台
- **Member**: 已注册的会员用户（个人 / 代理 / 服务商）
- **Guest**: 未登录的游客用户
- **AI Engine**: 后端 AI 调用层，统一管理 xAI、微调模型、知识库的调度
- **Knowledge Base**: 平台内部文档库（当前 collection_1c89e82d...），用于 RAG 检索增强
- **Fine-tuned Model**: 针对移民/英语学习场景微调的 LLaMA / Qwen 等开源模型
- **Vocabulary Deck**: 用户自定义的单词学习集合
- **Spaced Repetition**: 基于遗忘曲线的间隔重复记忆算法
- **Writing Assessment**: AI 驱动的英语写作批改（语法 / 词汇 / 结构 / 连贯性评分）

---

## Requirements

### R1: 响应式布局改造

**User Story:** AS a mobile user, I want the website to display properly on my phone, so that I can access AI-mmi on any device.

#### Acceptance Criteria

1. The system SHALL adapt page layout to screen widths of 320px, 768px, 1024px, and 1440px.
2. WHEN the viewport width is less than 768px, the system SHALL collapse the navigation menu into a hamburger toggle.
3. WHEN the viewport width is less than 768px, the system SHALL display the AI chat window in full-screen mode.
4. The system SHALL ensure all interactive elements (buttons, inputs, links) have a minimum touch target of 44x44 CSS pixels on touch devices.
5. WHILE a page is rendered, the system SHALL serve appropriately sized images based on the device pixel ratio (1x, 2x, 3x).

---

### R2: 移动端性能优化

**User Story:** AS a mobile user on a slow network, I want the pages to load quickly, so that I don't abandon the site.

#### Acceptance Criteria

1. The system SHALL deliver a First Contentful Paint (FCP) under 1.5 seconds on 4G connections.
2. The system SHALL lazy-load images that appear below the initial viewport fold.
3. The system SHALL defer non-critical JavaScript execution until after the initial render.
4. The system SHALL minify and bundle CSS and JavaScript assets in production builds.

---

### R3: AI Prompt 工程优化

**User Story:** AS a platform administrator, I want the AI to give more accurate and domain-specific responses, so that user satisfaction improves.

#### Acceptance Criteria

1. The system SHALL maintain separate system prompt templates for immigration consultation, education counseling, and English learning contexts.
2. WHEN a user selects English learning mode, the system SHALL inject the English learning system prompt into the AI request.
3. The system SHALL include chat history context from the last 5 exchanges within the same session when constructing prompts.
4. The system SHALL append relevant retrieved knowledge base snippets to prompts when the confidence score of knowledge base search exceeds 0.7.
5. The system SHALL log prompt versions and user satisfaction ratings for A/B comparison.

---

### R4: 移民/留学知识库增强

**User Story:** AS a user asking about visa policies, I want the AI to provide up-to-date and accurate information, so that I can make informed decisions.

#### Acceptance Criteria

1. The system SHALL support uploading structured knowledge documents (PDF, DOCX, Markdown) via the admin panel.
2. WHEN a document is uploaded, the system SHALL automatically chunk, embed, and index the document into the vector database within 60 seconds.
3. The system SHALL regenerate embeddings for all affected documents when the embedding model is updated.
4. WHILE a user asks a question in immigration mode, the system SHALL query the knowledge base and attach the top 3 relevant chunks to the AI prompt.
5. The system SHALL display the source document reference alongside AI responses that use knowledge base content.

---

### R5: 微调模型部署

**User Story:** AS a platform administrator, I want to deploy fine-tuned models for specific tasks, so that the AI provides more specialized and cost-effective responses.

#### Acceptance Criteria

1. The system SHALL support routing specific query types (English grammar check, IELTS essay scoring, visa eligibility check) to dedicated fine-tuned models.
2. WHEN a fine-tuned model is unavailable or times out after 10 seconds, the system SHALL fall back to the primary xAI model.
3. The system SHALL maintain a model registry that maps task types to model endpoints and their health status.
4. The system SHALL log the model used for each AI request for cost tracking and quality monitoring.
5. WHILE the fine-tuned model is warming up, the system SHALL route requests to the primary model without interruption.

---

### R6: 词汇学习模块

**User Story:** AS an English learner, I want to build and review vocabulary lists, so that I can expand my English vocabulary systematically.

#### Acceptance Criteria

1. The system SHALL allow members to create, rename, and delete personal vocabulary decks.
2. WHEN a member encounters an unfamiliar word during AI chat, the system SHALL provide a one-click option to add the word to a selected vocabulary deck.
3. The system SHALL store each vocabulary entry with word, phonetic transcription, definition in the user's language, part of speech, and 2 example sentences.
4. The system SHALL implement a spaced repetition review algorithm (SM-2) that schedules word reviews at intervals of 1, 3, 7, 14, and 30 days.
5. WHEN a member opens the vocabulary review page, the system SHALL present at most 20 words due for review in flashcard format.
6. The system SHALL track per-word review history including correct/incorrect counts and last review timestamp.

---

### R7: AI 语法纠错

**User Story:** AS an English learner, I want the AI to check my English writing for grammar errors, so that I can improve my writing accuracy.

#### Acceptance Criteria

1. The system SHALL provide a dedicated grammar check interface where members can input English text up to 5000 characters.
2. WHEN a member submits text for grammar check, the system SHALL return a list of errors with position, error type, suggestion, and explanation in the member's preferred language.
3. The system SHALL categorize errors into spelling, grammar, punctuation, word choice, and style.
4. The system SHALL display the corrected version of the full text with changes highlighted inline.
5. The system SHALL cache grammar check results for identical input text to reduce redundant API calls for 24 hours.

---

### R8: AI 对话练习

**User Story:** AS an English learner, I want to practice English conversation with the AI in realistic scenarios, so that I can improve my speaking confidence.

#### Acceptance Criteria

1. The system SHALL provide scenario-based conversation practice including immigration interview, daily conversation, academic discussion, and business meeting.
2. WHEN a member starts a conversation practice session, the system SHALL set the AI role (e.g., immigration officer, classmate) and display the scenario context.
3. The system SHALL support both text input and voice input for conversation practice.
4. WHEN a conversation session ends, the system SHALL provide a summary report with metrics on vocabulary range, grammar accuracy, fluency indicators, and improvement suggestions.
5. The system SHALL store conversation transcripts for members to review later.

---

### R9: AI 写作批改

**User Story:** AS an IELTS test taker, I want the AI to assess my English essays, so that I can understand my writing level and areas for improvement.

#### Acceptance Criteria

1. The system SHALL provide an essay submission interface supporting IELTS Task 1, IELTS Task 2, TOEFL Independent Writing, and free writing formats.
2. WHEN a member submits an essay, the system SHALL return scores in four dimensions: Task Achievement, Coherence & Cohesion, Lexical Resource, and Grammatical Range & Accuracy on a 0-9 band scale.
3. The system SHALL highlight specific sentences and provide inline feedback for each scoring dimension.
4. The system SHALL generate a rewritten model answer for comparison.
5. The system SHALL store essay history with scores and feedback for progress tracking over time.

---

### R10: 英语学习进度追踪

**User Story:** AS an English learner, I want to see my learning progress over time, so that I stay motivated and identify weak areas.

#### Acceptance Criteria

1. The system SHALL display a dashboard showing vocabulary learned count, words reviewed today, grammar exercises completed, and conversation practice hours for the current week.
2. The system SHALL generate a weekly progress report with trend charts for vocabulary growth and writing score trends.
3. WHEN a member achieves a learning milestone (e.g., 100 words mastered, 10 essays submitted), the system SHALL display a congratulatory notification.

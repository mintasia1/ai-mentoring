-- Migration: add_matching_ai_fields.sql
-- Adds AI matching fields: mentoring_style, embedding cache, ai_score, ai_explanation
-- Run once against your database before deploying the AI matching feature.
-- Compatible with MySQL 5.7+ (uses IF NOT EXISTS via stored procedure pattern)

-- ── mentor_profiles ──────────────────────────────────────────────────────────

ALTER TABLE mentor_profiles
    ADD COLUMN mentoring_style
        ENUM('career_advice','academic_guidance','networking','skill_building','all')
        NOT NULL DEFAULT 'all'
        COMMENT 'Preferred mentoring style offered by this mentor';

ALTER TABLE mentor_profiles
    ADD COLUMN embedding_cache
        MEDIUMTEXT DEFAULT NULL
        COMMENT 'JSON array of floats: OpenAI embedding of (practice_area + expertise + interests + bio)';

ALTER TABLE mentor_profiles
    ADD COLUMN embedding_cached_at
        TIMESTAMP NULL DEFAULT NULL
        COMMENT 'When the embedding was last generated; NULL means stale/not yet generated';

-- ── mentee_profiles ───────────────────────────────────────────────────────────

ALTER TABLE mentee_profiles
    ADD COLUMN mentoring_style
        ENUM('career_advice','academic_guidance','networking','skill_building','all')
        NOT NULL DEFAULT 'all'
        COMMENT 'Preferred mentoring style sought by this mentee';

ALTER TABLE mentee_profiles
    ADD COLUMN expectations
        TEXT DEFAULT NULL
        COMMENT 'Free-text: what the mentee expects from the mentorship';

ALTER TABLE mentee_profiles
    ADD COLUMN embedding_cache
        MEDIUMTEXT DEFAULT NULL
        COMMENT 'JSON array of floats: OpenAI embedding of (practice_area_preference + interests + goals + expectations + bio)';

ALTER TABLE mentee_profiles
    ADD COLUMN embedding_cached_at
        TIMESTAMP NULL DEFAULT NULL
        COMMENT 'When the embedding was last generated; NULL means stale/not yet generated';

-- ── matching_scores ───────────────────────────────────────────────────────────

ALTER TABLE matching_scores
    ADD COLUMN mentoring_style_match
        BOOLEAN NOT NULL DEFAULT FALSE
        COMMENT 'True if both profiles share the same mentoring style (or either chose "all")';

ALTER TABLE matching_scores
    ADD COLUMN ai_score
        DECIMAL(5,2) NOT NULL DEFAULT 0.00
        COMMENT 'Weighted semantic score component from OpenAI embeddings (0-60)';

ALTER TABLE matching_scores
    ADD COLUMN ai_explanation
        TEXT DEFAULT NULL
        COMMENT 'GPT-generated natural-language explanation of why this pair is a good match';

ALTER TABLE matching_scores
    ADD COLUMN algorithm_version
        VARCHAR(20) NOT NULL DEFAULT 'v2-ai'
        COMMENT 'Which version of the matching algorithm produced this row';


-- ============================================================
-- Test Seed Data: 10 Mentors + 10 Mentees
-- Password for all accounts: wTuGGy(E$!W~M,jJGO{0}
-- Mentors:  mentor001–010@mint-asia.com
-- Mentees:  student001–010@mint-asia.com
-- ============================================================

-- ── Users ─────────────────────────────────────────────────────────────────────
INSERT INTO users (email, password_hash, role, first_name, last_name, status) VALUES
-- Mentors
('mentor001@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentor','James','Chan','active'),
('mentor002@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentor','Priya','Nair','active'),
('mentor003@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentor','David','Wong','active'),
('mentor004@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentor','Sarah','Lam','active'),
('mentor005@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentor','Michael','Leung','active'),
('mentor006@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentor','Emily','Tse','active'),
('mentor007@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentor','Kevin','Ho','active'),
('mentor008@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentor','Natalie','Cheng','active'),
('mentor009@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentor','Raymond','Yip','active'),
('mentor010@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentor','Grace','Kwok','active'),
-- Mentees
('student001@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentee','Amy','Cheung','active'),
('student002@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentee','Brian','Liu','active'),
('student003@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentee','Catherine','Ng','active'),
('student004@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentee','Daniel','Fung','active'),
('student005@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentee','Elaine','Mak','active'),
('student006@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentee','Felix','Tsang','active'),
('student007@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentee','Gloria','Hui','active'),
('student008@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentee','Henry','Poon','active'),
('student009@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentee','Iris','Shum','active'),
('student010@mint-asia.com','$2y$12$TUiVOlFb.ADNK0iq7pwWTOYjudx.oyQBqcIQs4oWIVysdleT./n5u','mentee','Jason','Kwong','active');

-- ── Mentor Profiles ────────────────────────────────────────────────────────────
-- Uses last-inserted user IDs via subquery so order-independent
INSERT INTO mentor_profiles
    (user_id, alumni_id, graduation_year, programme_level, practice_area,
     current_position, company, expertise, interests, language, location, bio,
     max_mentees, current_mentees, is_verified, mentoring_style)
SELECT id, alumni_id, graduation_year, programme_level, practice_area,
       current_position, company, expertise, interests, language, location, bio,
       max_mentees, 0, 1, mentoring_style
FROM (
  SELECT (SELECT id FROM users WHERE email='mentor001@mint-asia.com') AS id,
    'AL2015001' AS alumni_id, 2015 AS graduation_year, 'LLM' AS programme_level,
    'Corporate Law' AS practice_area,
    'Senior Associate' AS current_position, 'King & Wood Mallesons' AS company,
    'Mergers & acquisitions, cross-border transactions, corporate governance, Hong Kong listing rules' AS expertise,
    'Capital markets, RegTech, legal innovation in financial services' AS interests,
    'English, Cantonese' AS language, 'Central, Hong Kong Island' AS location,
    'Senior associate at KWM with 8 years in cross-border M&A. Passionate about mentoring the next generation of corporate lawyers.' AS bio,
    3 AS max_mentees, 'career_advice' AS mentoring_style
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='mentor002@mint-asia.com'),
    'AL2012002', 2012, 'PhD',
    'International Law',
    'Legal Counsel', 'HKMA',
    'International trade law, WTO disputes, bilateral investment treaties, sanctions compliance',
    'Public international law, arbitration, policy reform',
    'English, Hindi', 'Admiralty, Hong Kong Island',
    'Former academic turned in-house counsel. Doctorate from Cambridge. Advises on cross-border regulatory issues at HKMA.',
    2, 'academic_guidance'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='mentor003@mint-asia.com'),
    'AL2018003', 2018, 'JD',
    'Criminal Law',
    'Barrister', 'Hong Kong Bar Association',
    'Criminal defence, judicial review, magistracy practice, police complaints',
    'Access to justice, pro bono work, criminal procedure reform',
    'English, Cantonese, Putonghua', 'Sheung Wan, Hong Kong Island',
    'Practising barrister since 2018 focusing on criminal defence and public law. Strong believer in access to justice.',
    3, 'all'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='mentor004@mint-asia.com'),
    'AL2010004', 2010, 'LLB',
    'Family Law',
    'Partner', 'Wilkinson & Grist',
    'Divorce proceedings, child custody, domestic violence injunctions, matrimonial assets',
    'Gender equality, mental health awareness, child welfare policy',
    'English, Cantonese', 'Mong Kok, Kowloon',
    'Partner at a leading family law firm. 14 years experience. Advocate for specialist family court reform.',
    2, 'skill_building'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='mentor005@mint-asia.com'),
    'AL2016005', 2016, 'LLM',
    'Intellectual Property',
    'IP Manager', 'Tencent',
    'Patent prosecution, trademark disputes, copyright licensing, trade secrets in tech sector',
    'AI & law, digital copyright, privacy technology',
    'English, Putonghua', 'Sha Tin, New Territories',
    'In-house IP counsel at Tencent HK. Previously in private practice at Hogan Lovells. Keen on AI-related IP issues.',
    3, 'skill_building'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='mentor006@mint-asia.com'),
    'AL2009006', 2009, 'LLB',
    'Employment Law',
    'Director', 'Labour Tribunal HK',
    'Employment contracts, wrongful dismissal, discrimination claims, trade union law',
    'Workers rights, gig economy, ESG compliance',
    'English, Cantonese', 'Kwun Tong, Kowloon',
    'Over 15 years in employment law. Currently advising the Labour Tribunal. Regular speaker on gig economy regulation.',
    2, 'all'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='mentor007@mint-asia.com'),
    'AL2020007', 2020, 'JD',
    'Property Law',
    'Solicitor', 'Deacons',
    'Conveyancing, commercial leases, mortgage disputes, strata title',
    'Smart cities, PropTech, urban planning law',
    'English, Cantonese', 'Tuen Mun, New Territories',
    'Junior solicitor at Deacons specialising in property transactions. Passionate about PropTech and digital conveyancing.',
    3, 'networking'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='mentor008@mint-asia.com'),
    'AL2014008', 2014, 'LLM',
    'Banking & Finance',
    'Vice President', 'HSBC Legal',
    'Loan documentation, derivatives, structured finance, Basel III compliance, HKMA regulatory requirements',
    'Fintech regulation, digital assets, CBDC policy',
    'English, Cantonese, Putonghua', 'Tsim Sha Tsui, Kowloon',
    'VP at HSBC Legal in the structured finance team. Advises on cross-border lending and digital asset regulation.',
    3, 'career_advice'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='mentor009@mint-asia.com'),
    'AL2011009', 2011, 'PhD',
    'Human Rights Law',
    'Assistant Professor', 'HKU Faculty of Law',
    'Constitutional law, freedom of expression, refugee law, international human rights mechanisms',
    'Civil society, UNHCR advocacy, comparative constitutionalism',
    'English, Cantonese', 'Pokfulam, Hong Kong Island',
    'Academic at HKU Law with a decade of scholarship in human rights and constitutional law. Active UNHCR volunteer.',
    2, 'academic_guidance'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='mentor010@mint-asia.com'),
    'AL2017010', 2017, 'LLM',
    'Tax Law',
    'Tax Counsel', 'PwC Legal',
    'Corporate tax planning, transfer pricing, stamp duty, double taxation treaties, IRD negotiations',
    'Tax technology, ESG reporting, cross-border restructuring',
    'English, Cantonese, Putonghua', 'Wan Chai, Hong Kong Island',
    'Tax counsel at PwC advising multinationals on HK and China tax structuring. Frequent contributor to tax policy consultations.',
    3, 'skill_building'
) AS src;

-- ── Mentee Profiles ────────────────────────────────────────────────────────────
INSERT INTO mentee_profiles
    (user_id, student_id, programme_level, year_of_study,
     interests, goals, practice_area_preference, language_preference,
     location, bio, mentoring_style, expectations)
SELECT id, student_id, programme_level, year_of_study,
       interests, goals, practice_area_preference, language_preference,
       location, bio, mentoring_style, expectations
FROM (
  SELECT (SELECT id FROM users WHERE email='student001@mint-asia.com') AS id,
    'S2024001' AS student_id, 'LLM' AS programme_level, 1 AS year_of_study,
    'Corporate governance, ESG, securities regulation, fintech' AS interests,
    'Secure a training contract at a top-tier corporate law firm and develop M&A skills' AS goals,
    'Corporate Law' AS practice_area_preference,
    'English, Cantonese' AS language_preference,
    'Causeway Bay, Hong Kong Island' AS location,
    'LLM student at CUHK Law. Previously worked at a boutique M&A advisory firm. Keen to break into magic circle firms.' AS bio,
    'career_advice' AS mentoring_style,
    'I would like guidance on crafting my CV for corporate law roles and understanding the HK legal market.' AS expectations
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='student002@mint-asia.com'),
    'S2023002', 'JD', 2,
    'Criminal procedure, human rights, access to justice, legal aid',
    'Qualify as a barrister and practise criminal defence in the magistracy',
    'Criminal Law',
    'English, Cantonese',
    'Sham Shui Po, Kowloon',
    'Second-year JD with a background in social work. Interested in public interest law and criminal justice reform.',
    'all',
    'Looking for a mentor with advocacy experience who can guide me through pupillage applications.'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='student003@mint-asia.com'),
    'S2024003', 'LLB', 3,
    'IP law, technology startups, copyright, software licensing',
    'Join a tech company as in-house IP counsel after graduation',
    'Intellectual Property',
    'English, Putonghua',
    'Sha Tin, New Territories',
    'Third-year LLB student with a part-time job at a tech startup. Enthusiastic about the intersection of law and technology.',
    'skill_building',
    'Hoping to learn about in-house career paths and gain practical IP drafting skills through mock exercises.'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='student004@mint-asia.com'),
    'S2022004', 'PhD', 1,
    'International trade law, WTO, investment arbitration, sanctions',
    'Publish research on investment treaty interpretation and pursue an academic career',
    'International Law',
    'English',
    'Tai Po, New Territories',
    'First-year PhD researcher focusing on investor-state dispute settlement. Holds a Cambridge LLM.',
    'academic_guidance',
    'Seeking academic mentorship on publishing strategies and navigating international law conferences.'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='student005@mint-asia.com'),
    'S2023005', 'LLM', 1,
    'Banking regulation, fintech, digital assets, crypto law',
    'Work in legal or compliance at a bank or fintech company in Hong Kong',
    'Banking & Finance',
    'English, Cantonese',
    'Yau Ma Tei, Kowloon',
    'LLM student specialising in financial law. Passed CFA Level I. Interested in the regulatory side of digital assets.',
    'career_advice',
    'I want to understand how to position myself for a legal or compliance role in HK banking or fintech.'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='student006@mint-asia.com'),
    'S2024006', 'LLB', 2,
    'Employment rights, discrimination law, labour policy, trade unions',
    'Practise employment law at a firm that handles both employer and employee side work',
    'Employment Law',
    'English, Cantonese',
    'Kwun Tong, Kowloon',
    'Second-year LLB student who volunteered at a community legal centre handling employment disputes.',
    'networking',
    'Would love to shadow a practitioner and expand my professional network in the employment law space.'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='student007@mint-asia.com'),
    'S2023007', 'JD', 3,
    'Family mediation, child law, social welfare, mental health law',
    'Qualify as a family law solicitor and eventually specialise in international child abduction',
    'Family Law',
    'English, Cantonese',
    'Tuen Mun, New Territories',
    'Final-year JD student with a psychology minor. Completed a placement at the Social Welfare Department.',
    'skill_building',
    'I want practical guidance on PCLL applications and building skills for family law practice.'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='student008@mint-asia.com'),
    'S2024008', 'LLM', 2,
    'Corporate tax, transfer pricing, China tax, cross-border structuring',
    'Become a tax lawyer advising multinational corporations on HK and PRC tax issues',
    'Tax Law',
    'English, Putonghua',
    'Wan Chai, Hong Kong Island',
    'Second-year LLM in taxation. Previously worked in Big 4 accounting tax advisory team. Seeking legal qualification.',
    'skill_building',
    'I want to understand the differences between tax advisory and legal practice, and how to make the transition.'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='student009@mint-asia.com'),
    'S2022009', 'LLB', 1,
    'Human rights, constitutional law, refugee law, public interest litigation',
    'Work at an NGO or international organisation on human rights advocacy',
    'Human Rights Law',
    'English, Cantonese',
    'Pokfulam, Hong Kong Island',
    'First-year LLB, passion for public interest law. Part of the Moot Court and Human Rights Society.',
    'academic_guidance',
    'I am looking for guidance on academic writing and the pathway to international human rights work.'
  UNION ALL
  SELECT (SELECT id FROM users WHERE email='student010@mint-asia.com'),
    'S2023010', 'LLM', 1,
    'Property development law, conveyancing, mortgage financing, urban redevelopment',
    'Join a property law team at a major firm or developer in-house legal department',
    'Property Law',
    'English, Cantonese',
    'Tsuen Wan, New Territories',
    'LLM student with a background in architecture. Interested in the legal aspects of urban development and PropTech.',
    'networking',
    'Hope to build connections in the property law sector and get advice on transitioning from architecture to law.'
) AS src;

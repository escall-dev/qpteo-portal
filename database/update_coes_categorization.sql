-- =============================================================
-- Migration Script: Centers of Excellence (COE) Categorization & Content
-- Target: Run directly in Hostinger phpMyAdmin (SQL tab)
-- =============================================================

-- 1. Add `category` column to `centers_of_excellence` table
-- If column doesn't exist, run this:
ALTER TABLE `centers_of_excellence` 
ADD COLUMN `category` ENUM('national', 'regional') NOT NULL DEFAULT 'national' AFTER `institution_name`,
ADD INDEX `idx_category` (`category`);

-- -------------------------------------------------------------
-- 2. Insert or Update Default Page Overview and Challenges Content in `settings`
-- -------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('coe_national_title', 'NATIONAL COEs'),
('coe_national_intro', 'The designated COEs serve as pivotal leaders in elevating teacher quality and strengthening the Philippine teacher education system. Leveraging their expertise in teacher preparation, research, curriculum innovation, policy engagement, and professional development, they are uniquely positioned to drive evidence-based reforms, foster workforce development, and tackle systemic challenges affecting educational equity and learner outcomes.'),
('coe_national_challenges_title', 'Priority challenges requiring national-level action'),
('coe_national_challenges', '[
  {
    "title": "Public Perception of the Teaching Profession",
    "description": "Negative perceptions of teaching related to salary, working conditions, career prospects, and professional status continue to discourage students from pursuing teaching careers. These perceptions contribute to challenges in attracting and retaining qualified teachers, particularly in high-need specialization areas."
  },
  {
    "title": "Poor Literacy and Content Knowledge of Teachers",
    "description": "Low licensure examination passing rates and widespread teacher-subject mismatches indicate gaps in teacher content mastery and preparedness. These challenges affect instructional quality and highlight the need to strengthen pre-service teacher education and professional development."
  },
  {
    "title": "Limited Pathways to Enter the Teaching Profession",
    "description": "TEIs are unevenly distributed across the country, with concentrations in urban centers and limited access in many provinces. This creates barriers for aspiring teachers in underserved and geographically isolated areas."
  },
  {
    "title": "Limited Field Experiences and Exposure to Real-World Classroom Teaching",
    "description": "Pre-service teachers receive fewer practicum hours than international benchmarks, limiting opportunities for sustained classroom immersion. Insufficient exposure to authentic teaching environments affects instructional competence, classroom management skills, and professional readiness."
  },
  {
    "title": "Mismatch Between Teacher Specialization and Subject Taught",
    "description": "A large proportion of teachers teach subjects outside their field of specialization due to shortages and limited availability of specialized teacher education programs. This weakens content mastery and affects the quality of instruction in key learning areas."
  },
  {
    "title": "Fragmented Quality Assurance System in Teacher Education",
    "description": "Quality assurance mechanisms in teacher education remain fragmented across multiple agencies, resulting in overlapping functions, inconsistent standards, and weak alignment among teacher preparation, licensure, and professional development systems."
  },
  {
    "title": "Lack of Coordination Across the Teacher Education Ecosystem",
    "description": "Weak coordination among agencies and stakeholders involved in teacher education contributes to gaps in teacher preparation, workforce planning, licensure, and professional development. Stronger alignment across the teacher education continuum is needed to improve system coherence and effectiveness."
  },
  {
    "title": "Limited Professional Development Opportunities for In-Service Teachers",
    "description": "Many in-service teachers face challenges in accessing relevant and high-quality professional development due to inequitable access, funding constraints, and misalignment with current classroom needs."
  },
  {
    "title": "Poor Literacy and Numeracy Performance of Basic Education Learners",
    "description": "Persistent literacy and numeracy challenges among learners and graduates indicate the need to strengthen foundational skills instruction, teacher capacity, and intervention programs to improve learning outcomes nationwide."
  }
]'),
('coe_regional_title', 'REGIONAL COEs'),
('coe_regional_intro', 'Drawing on their strengths in teacher preparation, research, curriculum innovation, extension services, and institutional partnerships, they can contribute to strengthening teacher quality, expanding specialized teacher education programs, promoting inclusive and equitable learning, and responding to regional workforce needs.'),
('coe_regional_challenges_title', 'Challenges that the Regional Teacher Education COEs need to address'),
('coe_regional_challenges', '[
  {
    "category": "Shortage of Specialized Teachers",
    "items": [
      {
        "title": "Severe Shortage of Early Childhood Education (ECE) Teachers",
        "description": "ECE specialists remain scarce across all regions. In some regions, ECE specialists account for only 0.008%–0.17% of the teacher workforce."
      },
      {
        "title": "Critical Shortage of Special Needs Education (SNED) Teachers",
        "description": "Across all regions, the shortage of in-service teachers specializing in Special Needs Education represents one of the most severe and consistent workforce gaps. Several regions report only two to 13 SNED specialists in the entire teaching workforce. The Cagayan Valley Region has only one TEI offering a bachelor’s degree in SNED."
      },
      {
        "title": "Underrepresentation of Values Education Specialists",
        "description": "Values Education programs are among the least offered teacher education programs. All regions report very low numbers of Values Education teachers. Several regions have only one to two TEIs offering BSEd Values Education."
      }
    ]
  },
  {
    "category": "Limited Access to Specialized Teacher Preparation Programs",
    "items": [
      {
        "title": "Limited Availability of Specialized Teacher Education Programs",
        "description": "Specialized teacher education programs such as Bachelor of Culture and Arts Education (BCAEd), Values Education, and Special Needs Education remain limited across regions, indicating insufficient specialized teacher preparation opportunities nationwide."
      }
    ]
  },
  {
    "category": "Inclusive and Alternative Education Gaps",
    "items": [
      {
        "title": "Indigenous Peoples Education (IPEd) and Inclusive Education Needs",
        "description": "Several regions have significant indigenous learner populations, particularly Cordillera Administrative Region and Cagayan Valley Region, highlighting the need for culturally responsive and context-specific teacher preparation."
      },
      {
        "title": "Multigrade Education Quality Concerns",
        "description": "Thousands of learners remain in multigrade settings across the regions, creating a need for teachers with specialized preparation in multigrade instruction and classroom management."
      },
      {
        "title": "Low Alternative Learning System (ALS) Coverage for Out-of-School Youth (OSY)",
        "description": "ALS participation remains low despite large out-of-school youth populations. Coverage ranges only from about 8% to 18% of OSY populations across regions."
      }
    ]
  },
  {
    "category": "Teacher Workforce and Deployment Challenges",
    "items": [
      {
        "title": "Variation in Teacher Availability Across Education Levels",
        "description": "Several regions show higher teacher-to-student ratios in Senior High School than in elementary and Junior High School, indicating greater staffing pressures and uneven teacher deployment across education levels."
      }
    ]
  }
]')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- -------------------------------------------------------------
-- 3. Update Existing or Seed Initial Institutions
-- -------------------------------------------------------------
-- National COEs
UPDATE `centers_of_excellence` SET `category` = 'national' WHERE `institution_name` LIKE '%Philippine Normal University Manila%';

INSERT INTO `centers_of_excellence` (`institution_name`, `category`, `region`, `province`, `address`, `status`, `doc_link`, `social_media_link`)
SELECT 'De La Salle University', 'national', 'National Capital Region', 'Metro Manila', 'Taft Avenue, Malate, Manila', 'active', 'https://www.dlsu.edu.ph', 'https://www.facebook.com/DLSU.Manila.100'
WHERE NOT EXISTS (SELECT 1 FROM `centers_of_excellence` WHERE `institution_name` LIKE '%De La Salle University%');

INSERT INTO `centers_of_excellence` (`institution_name`, `category`, `region`, `province`, `address`, `status`, `doc_link`, `social_media_link`)
SELECT 'Bicol University – Daraga', 'national', 'Region V', 'Albay', 'Sagpon, Daraga, Albay', 'active', 'https://bicol-u.edu.ph', 'https://www.facebook.com/BicolUniversity'
WHERE NOT EXISTS (SELECT 1 FROM `centers_of_excellence` WHERE `institution_name` LIKE '%Bicol University%');

-- Regional COEs
INSERT INTO `centers_of_excellence` (`institution_name`, `category`, `region`, `province`, `address`, `status`, `doc_link`, `social_media_link`)
SELECT 'Cebu Normal University', 'regional', 'Region VII', 'Cebu', 'Osmeña Boulevard in Brgy. San Antonio, Cebu City', 'active', 'https://www.cnu.edu.ph', 'https://www.facebook.com/cebunormaluniversity'
WHERE NOT EXISTS (SELECT 1 FROM `centers_of_excellence` WHERE `institution_name` LIKE '%Cebu Normal University%');

INSERT INTO `centers_of_excellence` (`institution_name`, `category`, `region`, `province`, `address`, `status`, `doc_link`, `social_media_link`)
SELECT 'Philippine Normal University – North Luzon', 'regional', 'Region II', 'Isabela', 'Alicia, Isabela', 'active', 'https://www.pnu.edu.ph', 'https://www.facebook.com/PNUNorthLuzonOfficial'
WHERE NOT EXISTS (SELECT 1 FROM `centers_of_excellence` WHERE `institution_name` LIKE '%Philippine Normal University%North Luzon%');

INSERT INTO `centers_of_excellence` (`institution_name`, `category`, `region`, `province`, `address`, `status`, `doc_link`, `social_media_link`)
SELECT 'University of the Cordilleras', 'regional', 'Cordillera Administrative Region', 'Benguet', 'Governor Pack Road, Baguio City, Benguet', 'active', 'https://www.uc-bcf.edu.ph', 'https://www.facebook.com/UCBaguioOfficial'
WHERE NOT EXISTS (SELECT 1 FROM `centers_of_excellence` WHERE `institution_name` LIKE '%University of the Cordilleras%');

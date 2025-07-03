# Database Schema

## `categories` Table

| Column      | Type        | Properties                               |
|-------------|-------------|------------------------------------------|
| id          | INT         | PRIMARY KEY, AUTO_INCREMENT, NOT NULL    |
| name        | VARCHAR(255)| NOT NULL                                 |
| slug        | VARCHAR(255)| NOT NULL, UNIQUE                         |
| description | TEXT        | NULL                                     |
| parent_id   | INT         | FOREIGN KEY (categories.id), NULL        |
| created_at  | TIMESTAMP   | DEFAULT CURRENT_TIMESTAMP                |
| updated_at  | TIMESTAMP   | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

## `posts` Table

| Column           | Type        | Properties                               |
|------------------|-------------|------------------------------------------|
| id               | INT         | PRIMARY KEY, AUTO_INCREMENT, NOT NULL    |
| title            | VARCHAR(255)| NOT NULL                                 |
| slug             | VARCHAR(255)| NOT NULL, UNIQUE                         |
| content          | TEXT        | NOT NULL                                 |
| category_id      | INT         | FOREIGN KEY (categories.id), NULL        |
| status           | VARCHAR(50) | NOT NULL, DEFAULT 'draft'                |
| featured_image   | VARCHAR(255)| NULL                                     |
| meta_description | VARCHAR(255)| NULL                                     |
| meta_keywords    | VARCHAR(255)| NULL                                     |
| excerpt          | TEXT        | NULL                                     |
| user_id          | INT         | FOREIGN KEY (users.id), NULL (inferred)  |
| created_at       | TIMESTAMP   | DEFAULT CURRENT_TIMESTAMP                |
| updated_at       | TIMESTAMP   | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

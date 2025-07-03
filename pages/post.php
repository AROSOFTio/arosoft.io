<?php
// pages/post.php
global $conn;
global $page_title;
global $meta_description;
global $meta_keywords;
global $site_settings;

$post_data = null;
$is_preview = false;
$base_blog_url_for_return = rtrim(BASE_URL, '/') . '/blog';

if (isset($_GET['preview_id'], $_GET['token']) && isset($_SESSION['admin_user_id'])) {
    $preview_post_id = (int)$_GET['preview_id'];
    $preview_token   = $_GET['token'];
    if (validate_preview_token($preview_post_id, $preview_token)) {
        $is_preview = true;
        $sql = "
            SELECT posts.*, categories.name AS category_name, categories.slug AS category_slug
            FROM posts
            LEFT JOIN categories ON posts.category_id = categories.id
            WHERE posts.id = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $preview_post_id);
        }
    } else {
        $meta_description = "Invalid or expired preview link.";
    }
} else {
    $post_slug_or_id = $_GET['slug'] ?? ($_GET['id'] ?? null);
    if ($post_slug_or_id) {
        $sql = "
            SELECT posts.*, categories.name AS category_name, categories.slug AS category_slug
            FROM posts
            LEFT JOIN categories ON posts.category_id = categories.id
            WHERE posts.status = 'published' AND
        ";
        if (isset($_GET['slug'])) {
            $sql .= "posts.slug = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $post_slug_or_id);
            }
        } else {
            $post_id_int = (int)$post_slug_or_id;
            $sql .= "posts.id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $post_id_int);
            }
        }
    }
}

if (!empty($stmt)) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $post_data = $result->fetch_assoc();
            $page_title = esc_html($post_data['title'])
                        . ($is_preview ? " (Preview)" : "")
                        . " - Blog | " . SITE_NAME;

            if (!empty($post_data['meta_description'])) {
                $meta_description = esc_html($post_data['meta_description']);
            } elseif (!empty($post_data['excerpt'])) {
                $meta_description = esc_html(generate_excerpt($post_data['excerpt'], 160, ''));
            } else {
                $meta_description = esc_html(generate_excerpt($post_data['content'], 160, ''));
            }
            $meta_keywords = !empty($post_data['meta_keywords'])
                           ? esc_html($post_data['meta_keywords'])
                           : '';
        }
    }
    $stmt->close();
} elseif (!$is_preview && isset($post_slug_or_id)) {
    error_log("Error preparing statement for single post: " . $conn->error);
}

if (empty($meta_description) && !$post_data && isset($site_settings['site_tagline'])) {
    $meta_description = esc_html($site_settings['site_tagline']);
} elseif (empty($meta_description) && !$post_data) {
    $meta_description = "The requested content could not be found on " . SITE_NAME;
}
if (!isset($meta_keywords)) {
    $meta_keywords = '';
}
?>

<?php
if ($post_data && !empty($post_data['slug'])) {
    $canonical_url = rtrim(BASE_URL, '/') . '/' . esc_html($post_data['slug']);
    echo '<link rel="canonical" href="' . $canonical_url . '" />' . "\n";
}
?>

<!--
  We removed the large top padding (py-16) so that the content starts
  immediately below the header bar. The container is set to max-w-5xl (same
  width as your blog listing), so everything lines up exactly.
-->
<section class="bg-background pb-16 animate-fade-in-up">
  <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-5xl">
    <?php if ($is_preview && $post_data): ?>
      <div class="mb-6 px-4 py-3 bg-highlight/10 border border-highlight text-highlight rounded-md text-sm text-center">
        <i data-lucide="alert-triangle" class="inline-block w-4 h-4 mr-1"></i>
        You are viewing a DRAFT PREVIEW. This post is not yet published
        <?php if ($post_data['status'] !== 'draft'): ?>
          (Current status: <?= esc_html($post_data['status']); ?>)
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($post_data): ?>
      <article class="bg-base-100 p-6 sm:p-8 md:p-10 rounded-xl shadow-2xl">
        <header class="mb-8 pb-6 border-b border-base-200">
          <?php if (!empty($post_data['category_name']) && !empty($post_data['category_slug'])):
            $category_url = rtrim(BASE_URL, '/') . '/blog/category/' . esc_html($post_data['category_slug']);
          ?>
            <a href="<?= $category_url; ?>"
               class="text-sm font-semibold uppercase tracking-wider text-secondary hover:text-highlight transition-colors"
            >
              <?= esc_html($post_data['category_name']); ?>
            </a>
          <?php endif; ?>
          <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl font-bold text-text mt-3 mb-4 leading-tight">
            <?= esc_html($post_data['title']); ?>
          </h1>
          <div class="flex items-center text-text/70 text-sm space-x-4">
            <span>
              <i data-lucide="calendar-days" class="inline-block w-4 h-4 mr-1"></i>
              <?= esc_html(format_date($post_data['created_at'])); ?>
            </span>
            <?php if (!empty($post_data['updated_at']) && format_date($post_data['updated_at']) !== format_date($post_data['created_at'])): ?>
              <span>
                <i data-lucide="edit-3" class="inline-block w-4 h-4 mr-1"></i>
                <?= esc_html(format_date($post_data['updated_at'])); ?>
              </span>
            <?php endif; ?>
          </div>
        </header>

        <?php if (!empty($post_data['featured_image'])): ?>
          <figure class="mb-8 rounded-lg overflow-hidden shadow-lg aspect-video">
            <img
              src="<?= esc_url(BASE_URL . 'uploads/' . $post_data['featured_image']); ?>"
              alt="<?= esc_html($post_data['title']); ?>"
              class="w-full h-full object-cover"
              onError="this.style.display='none'; this.parentElement.innerHTML = '<div class=\'w-full h-full bg-base-200 flex items-center justify-center text-text/60\'><i data-lucide=\'image-off\' class=\'w-16 h-16\'></i><p>Image not available</p></div>'; lucide.createIcons();"
            />
          </figure>
        <?php endif; ?>

        <div
          class="prose prose-lg prose-text text-text max-w-none
                 prose-headings:font-display prose-headings:text-text
                 prose-p:text-text/80 prose-p:leading-relaxed
                 prose-a:text-secondary prose-a:no-underline prose-a:hover:text-highlight prose-a:hover:underline
                 prose-strong:text-text
                 prose-blockquote:text-text/70 prose-blockquote:border-highlight
                 prose-code:text-accent prose-code:bg-base-200 prose-code:px-1 prose-code:py-0.5 prose-code:rounded
                 prose-pre:bg-base-200 prose-pre:text-text prose-pre:p-4 prose-pre:rounded-md prose-pre:overflow-x-auto
                 prose-ul:list-disc prose-ul:ml-5 prose-ol:list-decimal prose-ol:ml-5
                 prose-li:marker:text-secondary
                 prose-img:rounded-lg prose-img:shadow-md"
        >
          <?= $post_data['content']; ?>
        </div>

        <?php if (!empty($post_data['meta_keywords'])): ?>
          <div class="mt-10 pt-6 border-t border-base-200">
            <p class="text-sm text-text/70 flex items-center">
              <i data-lucide="tags" class="w-4 h-4 mr-2 text-secondary"></i>
              <span class="font-semibold mr-1">Keywords:</span>
              <span class="italic"><?= esc_html($post_data['meta_keywords']); ?></span>
            </p>
          </div>
        <?php endif; ?>

        <div class="mt-12 pt-8 border-t border-base-200">
          <h3 class="font-display text-2xl font-semibold text-text mb-6">Comments</h3>
          <p class="text-text/70 italic">
            Comments are currently disabled or not yet implemented for this post.
          </p>
        </div>
      </article>

      <div class="mt-12 text-center">
        <a
          href="<?= $base_blog_url_for_return; ?>"
          class="inline-flex items-center px-6 py-3 text-base font-medium text-base-100 bg-secondary hover:bg-secondary-hover rounded-lg shadow transition-colors group"
        >
          <i data-lucide="arrow-left" class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform"></i>
          Return to Blog
        </a>
      </div>
    <?php else: ?>
      <div class="text-center py-20">
        <i data-lucide="file-question" class="w-24 h-24 text-text/60 mx-auto mb-6"></i>
        <h1 class="font-display text-4xl font-bold text-text mb-3">Post Not Found</h1>
        <p class="text-lg text-text/70 mb-8">
          Sorry, we couldn't find the blog post you were looking for, or the preview link is invalid/expired.
        </p>
        <a
          href="<?= $base_blog_url_for_return; ?>"
          class="inline-flex items-center px-6 py-3 text-base font-medium text-base-100 bg-secondary hover:bg-secondary-hover rounded-lg shadow transition-colors"
        >
          <i data-lucide="arrow-left-circle" class="w-5 h-5 mr-2"></i>Return to Blog
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php
// pages/blog.php
global $conn;
global $page;

$posts_per_page       = defined('POSTS_PER_PAGE') ? POSTS_PER_PAGE : 10;
$current_page_number  = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
if ($current_page_number < 1) {
    $current_page_number = 1;
}
$offset = ($current_page_number - 1) * $posts_per_page;

$category_filter_slug = isset($_GET['category']) ? trim($_GET['category']) : null;
$search_term          = isset($_GET['search']) ? trim($_GET['search']) : null;

// --- Build base SQL queries ---
$sql_select_posts = "
    SELECT
        posts.id,
        posts.title,
        posts.slug,
        posts.content,
        posts.excerpt,
        posts.featured_image,
        posts.created_at,
        posts.status,
        categories.name AS category_name,
        categories.slug AS category_slug
    FROM posts
    LEFT JOIN categories ON posts.category_id = categories.id
";
$sql_count_posts = "
    SELECT COUNT(posts.id) AS total
    FROM posts
    LEFT JOIN categories ON posts.category_id = categories.id
";

$where_clauses = ["posts.status = 'published'"];
$params         = [];
$types          = "";

if ($search_term) {
    $where_clauses[]     = "(posts.title LIKE ? OR posts.content LIKE ?)";
    $search_like         = "%" . $search_term . "%";
    array_push($params, $search_like, $search_like);
    $types .= "ss";
}
if ($category_filter_slug) {
    $where_clauses[] = "categories.slug = ?";
    array_push($params, $category_filter_slug);
    $types .= "s";
}

if (!empty($where_clauses)) {
    $final_where_clause   = " WHERE " . implode(" AND ", $where_clauses);
    $sql_select_posts    .= $final_where_clause;
    $sql_count_posts     .= $final_where_clause;
}

// --- Count total posts with filters ---
$stmt_count   = $conn->prepare($sql_count_posts);
$total_posts  = 0;
$total_pages  = 0;
if ($stmt_count) {
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    if ($stmt_count->execute()) {
        $result_count = $stmt_count->get_result();
        if ($result_count) {
            $row          = $result_count->fetch_assoc();
            $total_posts  = $row['total'] ?? 0;
            $total_pages  = $posts_per_page > 0 ? ceil($total_posts / $posts_per_page) : 0;
        }
    }
    $stmt_count->close();
}

// --- Fetch posts for the current page ---
$sql_select_posts .= " ORDER BY posts.created_at DESC LIMIT ? OFFSET ?";
$params_for_select = $params;
array_push($params_for_select, $posts_per_page, $offset);
$types_for_select = $types . "ii";

$stmt_select = $conn->prepare($sql_select_posts);
$posts       = [];
if ($stmt_select) {
    $stmt_select->bind_param($types_for_select, ...$params_for_select);
    if ($stmt_select->execute()) {
        $result = $stmt_select->get_result();
        if ($result) {
            $posts = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
    $stmt_select->close();
}

// --- Fetch all categories for filter dropdown ---
$all_categories_sql    = "SELECT id, name, slug FROM categories ORDER BY name ASC";
$all_categories_result = $conn->query($all_categories_sql);
$all_categories        = ($all_categories_result && $all_categories_result->num_rows > 0)
                            ? $all_categories_result->fetch_all(MYSQLI_ASSOC)
                            : [];
?>

<section class="bg-background bg-opacity-30 py-16 md:py-20 animate-fade-in-up">
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <header class="text-center mb-12 md:mb-16">
      <h1 class="font-display text-4xl sm:text-5xl font-bold text-text mb-3">Our Blog</h1>
      <p class="text-lg text-secondary max-w-xl mx-auto">
        Insights, news, and articles from the <?= esc_html(SITE_NAME); ?> team.
      </p>
      <span class="section-title-underline"></span>
    </header>

    <!-- Filter Section -->
    <div class="mb-10 p-6 bg-base-100 rounded-lg shadow-md">
      <h3 class="text-xl font-semibold text-text mb-4">Filter Posts</h3>
      <form method="GET" action="<?= rtrim(BASE_URL, '/'); ?>/blog" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 items-end">
        <div>
          <label for="search" class="block text-sm font-medium text-text/70 mb-1">Search</label>
          <input
            type="text"
            name="search"
            id="search"
            value="<?= esc_html($search_term ?? ''); ?>"
            placeholder="Keywords..."
            class="w-full px-4 py-3 bg-base-200 border border-neutral-light rounded-md text-text focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
          />
        </div>
        <div>
          <label for="category" class="block text-sm font-medium text-text/70 mb-1">Category</label>
          <select
            name="category"
            id="category"
            class="w-full px-4 py-3 bg-base-200 border border-neutral-light rounded-md text-text focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
            onchange="this.form.submit()"
          >
            <option value="">All Categories</option>
            <?php foreach ($all_categories as $cat): ?>
              <option
                value="<?= esc_html($cat['slug']); ?>"
                <?= ($category_filter_slug === $cat['slug']) ? 'selected' : '' ?>
              ><?= esc_html($cat['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <button
            type="submit"
            class="w-full sm:w-auto px-6 py-3 bg-secondary hover:bg-secondary-hover text-white font-medium rounded-md shadow transition-colors flex items-center justify-center"
          >
            <i data-lucide="filter" class="w-5 h-5 mr-2"></i>Apply Filters
          </button>
          <?php if ($search_term || $category_filter_slug): ?>
            <a
              href="<?= rtrim(BASE_URL, '/'); ?>/blog"
              class="block mt-2 sm:mt-0 sm:ml-2 text-xs text-text/70 hover:text-secondary underline"
            >Clear Filters</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <?php if (!empty($posts)): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($posts as $idx => $post): ?>
          <article
            class="bg-base-100 rounded-xl shadow-xl overflow-hidden flex flex-col transition-all duration-300 hover:shadow-secondary/20 hover:-translate-y-1 animate-fade-in-up"
            style="animation-delay: <?= $idx * 100; ?>ms"
          >
            <?php $post_url = rtrim(BASE_URL, '/') . '/' . esc_html($post['slug']); ?>
            <?php if (!empty($post['featured_image'])): ?>
              <a href="<?= $post_url; ?>">
                <img
                  src="<?= BASE_URL . 'uploads/' . esc_html($post['featured_image']); ?>"
                  alt="<?= esc_html($post['title']); ?>"
                  class="w-full h-56 object-cover transition-transform duration-300"
                />
              </a>
            <?php else: ?>
              <a href="<?= $post_url; ?>">
                <div class="w-full h-56 bg-base-200 flex items-center justify-center">
                  <i data-lucide="image-off" class="w-16 h-16 text-text/60"></i>
                </div>
              </a>
            <?php endif; ?>
            <div class="p-6 flex flex-col flex-grow">
              <div class="mb-3 flex items-center space-x-2">
                <?php if (!empty($post['category_name'])):
                  $category_url = rtrim(BASE_URL, '/') . '/blog/category/' . esc_html($post['category_slug']);
                ?>
                  <a
                    href="<?= $category_url; ?>"
                    class="text-xs font-semibold uppercase tracking-wider text-secondary hover:underline"
                  ><?= esc_html($post['category_name']); ?></a>
                <?php endif; ?>
                <span class="text-xs text-text/60 ml-auto"><?= esc_html(format_date($post['created_at'])); ?></span>
              </div>
              <h2 class="font-display text-xl lg:text-2xl font-semibold text-text mb-3 leading-tight">
                <a href="<?= $post_url; ?>" class="hover:text-secondary transition-colors"><?= esc_html($post['title']); ?></a>
              </h2>
              <p class="text-text/70 text-sm mb-5 flex-grow line-clamp-3">
                <?php
                  $display_excerpt = !empty($post['excerpt'])
                    ? $post['excerpt']
                    : generate_excerpt($post['content'], 120);
                  echo esc_html($display_excerpt);
                ?>
              </p>
              <div class="mt-auto">
                <a
                  href="<?= $post_url; ?>"
                  class="inline-flex items-center text-sm font-medium text-secondary hover:text-secondary-hover transition-colors"
                >Read More <i data-lucide="arrow-right" class="w-4 h-4 ml-1.5 transition-transform group-hover:translate-x-1"></i></a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if ($total_pages > 1):
        $base_url = rtrim(BASE_URL, '/') . '/blog';
        if ($category_filter_slug) {
          $base_url .= '/category/' . urlencode($category_filter_slug);
        }
        $search_query = $search_term ? '?search=' . urlencode($search_term) : '';
      ?>
        <nav class="mt-12 md:mt-16 pt-8 border-t border-neutral-light flex justify-center items-center space-x-1 sm:space-x-2" aria-label="Pagination">
          <?php if ($current_page_number > 1): ?>
            <a
              href="<?= $base_url . '/page/' . ($current_page_number - 1) . $search_query; ?>"
              class="px-3 py-2 sm:px-4 text-sm text-text/80 bg-base-200 hover:bg-secondary hover:text-white rounded-md transition-colors flex items-center"
            ><i data-lucide="chevron-left" class="w-4 h-4 sm:mr-1"></i><span class="hidden sm:inline">Previous</span></a>
          <?php endif; ?>

          <?php
            $links_to_show = 5;
            $start         = max(1, $current_page_number - floor($links_to_show / 2));
            $end           = min($total_pages, $start + $links_to_show - 1);
            if ($end - $start + 1 < $links_to_show) {
              $start = max(1, $end - $links_to_show + 1);
            }

            if ($start > 1) {
              echo '<a href="'.$base_url.'/page/1'.$search_query.'" class="px-3 py-2 sm:px-4 text-sm text-text/80 bg-base-200 hover:bg-secondary hover:text-white rounded-md transition-colors">1</a>';
              if ($start > 2) {
                echo '<span class="px-3 py-2 sm:px-4 text-sm text-text/60">...</span>';
              }
            }

            for ($i = $start; $i <= $end; $i++):
          ?>
            <?php if ($i == $current_page_number): ?>
              <span aria-current="page" class="px-3 py-2 sm:px-4 text-sm font-semibold text-white bg-secondary rounded-md"><?= $i; ?></span>
            <?php else: ?>
              <a
                href="<?= $base_url . '/page/' . $i . $search_query; ?>"
                class="px-3 py-2 sm:px-4 text-sm text-text/80 bg-base-200 hover:bg-secondary hover:text-white rounded-md transition-colors"
              ><?= $i; ?></a>
            <?php endif; ?>
          <?php endfor;

            if ($end < $total_pages) {
              if ($end < $total_pages - 1) {
                echo '<span class="px-3 py-2 sm:px-4 text-sm text-text/60">...</span>';
              }
              echo '<a href="'.$base_url.'/page/'.$total_pages.$search_query.'" class="px-3 py-2 sm:px-4 text-sm text-text/80 bg-base-200 hover:bg-secondary hover:text-white rounded-md transition-colors">'.$total_pages.'</a>';
            }
          ?>

          <?php if ($current_page_number < $total_pages): ?>
            <a
              href="<?= $base_url . '/page/' . ($current_page_number + 1) . $search_query; ?>"
              class="px-3 py-2 sm:px-4 text-sm text-text/80 bg-base-200 hover:bg-secondary hover:text-white rounded-md transition-colors flex items-center"
            ><span class="hidden sm:inline">Next</span> <i data-lucide="chevron-right" class="w-4 h-4 sm:ml-1"></i></a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>

    <?php else: ?>
      <div class="text-center py-12">
        <i data-lucide="search-x" class="w-16 h-16 text-text/60 mx-auto mb-4"></i>
        <h2 class="text-2xl font-semibold text-text/70 mb-2">No Posts Found</h2>
        <p class="text-text/60">It seems there are no blog posts matching your criteria. Try adjusting your filters or check back later!</p>
        <?php if ($search_term || $category_filter_slug): ?>
          <a
            href="<?= rtrim(BASE_URL, '/'); ?>/blog"
            class="mt-6 inline-flex items-center px-5 py-3 bg-secondary hover:bg-secondary-hover text-white font-medium rounded-lg shadow transition-colors"
          >Clear Filters and Show All Posts</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

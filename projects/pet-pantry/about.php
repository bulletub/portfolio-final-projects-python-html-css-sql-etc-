<?php
require 'database.php';

// Fetch page content from CMS
$pageContent = [];
try {
    $stmt = $pdo->query("SELECT section_key, content_value FROM cms_pages WHERE page_name='about'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pageContent[$row['section_key']] = $row['content_value'];
    }
} catch (Exception $e) {
    // If table doesn't exist yet, use defaults
}

// Set default values if not in database
$heroTitle = $pageContent['hero_title'] ?? 'Meet the <span class="text-yellow-300">Creators</span> Behind PetPantry+';
$heroSubtitle = $pageContent['hero_subtitle'] ?? 'We\'re a passionate team of students from TIP Quezon City, dedicated to building the ultimate pet supply platform for our System Integration and Architecture finals project.';
$heroBadgeText = $pageContent['hero_badge_text'] ?? 'TIP-QC Academic Project 2025';
$teamSectionTitle = $pageContent['team_section_title'] ?? 'Our Development Team';
$teamSectionSubtitle = $pageContent['team_section_subtitle'] ?? 'Five talented individuals working together to revolutionize pet care through technology';

$teamMembers = json_decode($pageContent['team_members'] ?? '[]', true);
if (empty($teamMembers)) {
    // Default team members if none in database
    $teamMembers = [
        ['name' => 'Your Name Here', 'role' => 'Project Lead', 'bio' => 'Leading the project vision and coordinating our team to deliver an exceptional e-commerce platform. Passionate about creating seamless user experiences.', 'image' => '', 'color' => 'orange'],
        ['name' => 'Your Name Here', 'role' => 'Backend Developer', 'bio' => 'Architecting robust server-side solutions and database systems. Ensuring secure, scalable, and efficient data management for PetPantry+.', 'image' => '', 'color' => 'blue'],
        ['name' => 'Your Name Here', 'role' => 'Frontend Developer', 'bio' => 'Crafting beautiful, responsive interfaces that delight users. Bringing design concepts to life with modern web technologies and best practices.', 'image' => '', 'color' => 'purple'],
        ['name' => 'Your Name Here', 'role' => 'UI/UX Designer', 'bio' => 'Designing intuitive user experiences and creating visual assets. Ensuring every interaction is smooth, accessible, and visually appealing.', 'image' => '', 'color' => 'green'],
        ['name' => 'Your Name Here', 'role' => 'QA & Testing', 'bio' => 'Ensuring quality through rigorous testing and validation. Finding and fixing issues before they reach users, maintaining high standards throughout.', 'image' => '', 'color' => 'pink']
    ];
}

$projectGoals = json_decode($pageContent['project_goals'] ?? '[]', true);
if (empty($projectGoals)) {
    $projectGoals = [
        'Build a fully functional e-commerce platform for pet supplies',
        'Implement modern web technologies and best practices',
        'Demonstrate system integration and architecture concepts',
        'Create a seamless user experience for pet owners'
    ];
}

$projectTechnologies = json_decode($pageContent['project_technologies'] ?? '[]', true);
if (empty($projectTechnologies)) {
    $projectTechnologies = [
        'PHP & MySQL for backend development',
        'Tailwind CSS for modern, responsive design',
        'PayPal integration for secure payments',
        'PHPMailer for email notifications'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us - Meet Our Team | PetPantry+</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="index.css">
  
  <style>
    .team-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .team-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(255, 107, 53, 0.2);
    }
    .team-img-placeholder {
      background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
      position: relative;
      overflow: hidden;
    }
    .team-img-placeholder::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 80px;
      opacity: 0.3;
    }
  </style>
</head>
<body class="bg-gray-50">

  <?php include 'header.php'; ?>

  <!-- Hero Section -->
  <section class="relative bg-gradient-to-br from-orange-500 to-orange-600 text-white pt-32 pb-20 mt-16">
    <div class="max-w-7xl mx-auto px-6 text-center">
      <h1 class="text-5xl md:text-6xl font-extrabold mb-6">
        <?php echo $heroTitle; ?>
      </h1>
      <p class="text-xl md:text-2xl text-orange-100 max-w-3xl mx-auto leading-relaxed">
        <?php echo htmlspecialchars($heroSubtitle); ?>
      </p>
      <div class="mt-8 inline-flex items-center gap-3 bg-white/10 backdrop-blur-sm px-6 py-3 rounded-full">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
        </svg>
        <span class="font-semibold"><?php echo htmlspecialchars($heroBadgeText); ?></span>
      </div>
    </div>
  </section>

  <!-- Team Members Section -->
  <section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-6">
      
      <!-- Section Header -->
      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($teamSectionTitle); ?></h2>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
          <?php echo htmlspecialchars($teamSectionSubtitle); ?>
        </p>
      </div>

      <!-- Team Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-8">
        
        <?php foreach ($teamMembers as $index => $member): 
          $colorMap = [
            'orange' => ['from' => 'from-orange-400', 'to' => 'to-orange-600', 'bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
            'blue' => ['from' => 'from-blue-400', 'to' => 'to-blue-600', 'bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
            'purple' => ['from' => 'from-purple-400', 'to' => 'to-purple-600', 'bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
            'green' => ['from' => 'from-green-400', 'to' => 'to-green-600', 'bg' => 'bg-green-100', 'text' => 'text-green-600'],
            'pink' => ['from' => 'from-pink-400', 'to' => 'to-pink-600', 'bg' => 'bg-pink-100', 'text' => 'text-pink-600'],
            'red' => ['from' => 'from-red-400', 'to' => 'to-red-600', 'bg' => 'bg-red-100', 'text' => 'text-red-600'],
            'yellow' => ['from' => 'from-yellow-400', 'to' => 'to-yellow-600', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
            'indigo' => ['from' => 'from-indigo-400', 'to' => 'to-indigo-600', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-600']
          ];
          $color = $colorMap[$member['color'] ?? 'orange'];
        ?>
        
        <!-- Team Member <?php echo $index + 1; ?> -->
        <div class="team-card bg-white rounded-2xl shadow-lg overflow-hidden border-2 border-gray-100">
          <!-- Picture Holder -->
          <div class="team-img-placeholder w-full h-64 bg-gradient-to-br <?php echo $color['from'] . ' ' . $color['to']; ?> flex items-center justify-center">
            <?php if (!empty($member['image']) && file_exists($member['image'])): ?>
              <img src="<?php echo htmlspecialchars($member['image']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="w-full h-full object-cover">
            <?php else: ?>
              <div class="text-white text-6xl">👤</div>
            <?php endif; ?>
          </div>
          
          <!-- Info Section -->
          <div class="p-6">
            <!-- Name -->
            <h3 class="text-2xl font-bold text-gray-900 mb-2">
              <?php echo htmlspecialchars($member['name']); ?>
            </h3>
            
            <!-- Role -->
            <div class="inline-block <?php echo $color['bg'] . ' ' . $color['text']; ?> px-3 py-1 rounded-full text-sm font-semibold mb-4">
              <?php echo htmlspecialchars($member['role']); ?>
            </div>
            
            <!-- Bio -->
            <p class="text-gray-600 text-sm leading-relaxed">
              <?php echo htmlspecialchars($member['bio']); ?>
            </p>
          </div>
        </div>
        
        <?php endforeach; ?>

      </div>

    </div>
  </section>

  <!-- Project Info Section -->
  <section class="py-20 bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="max-w-7xl mx-auto px-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
        
        <!-- Stat 1 -->
        <div class="bg-white rounded-xl p-8 shadow-lg">
          <div class="text-5xl mb-4">🎓</div>
          <h3 class="text-3xl font-bold text-orange-600 mb-2">TIP-QC</h3>
          <p class="text-gray-600">Technological Institute of the Philippines - Quezon City</p>
        </div>

        <!-- Stat 2 -->
        <div class="bg-white rounded-xl p-8 shadow-lg">
          <div class="text-5xl mb-4">💻</div>
          <h3 class="text-3xl font-bold text-orange-600 mb-2">Finals Project</h3>
          <p class="text-gray-600">System Integration and Architecture</p>
        </div>

        <!-- Stat 3 -->
        <div class="bg-white rounded-xl p-8 shadow-lg">
          <div class="text-5xl mb-4">📅</div>
          <h3 class="text-3xl font-bold text-orange-600 mb-2">2025</h3>
          <p class="text-gray-600">Academic Year 2024-2025</p>
        </div>

      </div>

      <!-- Project Description -->
      <div class="mt-16 bg-white rounded-2xl p-10 shadow-xl border-2 border-orange-100">
        <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">About This Project</h2>
        <div class="grid md:grid-cols-2 gap-8">
          <div>
            <h3 class="text-xl font-semibold text-orange-600 mb-3">🎯 Project Goals</h3>
            <ul class="space-y-2 text-gray-700">
              <?php foreach ($projectGoals as $goal): ?>
              <li class="flex items-start gap-2">
                <span class="text-orange-500">•</span>
                <span><?php echo htmlspecialchars($goal); ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div>
            <h3 class="text-xl font-semibold text-orange-600 mb-3">⚙️ Technologies Used</h3>
            <ul class="space-y-2 text-gray-700">
              <?php foreach ($projectTechnologies as $tech): ?>
              <li class="flex items-start gap-2">
                <span class="text-orange-500">•</span>
                <span><?php echo htmlspecialchars($tech); ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </section>

  <?php include 'footer.php'; ?>
  <?php include 'disclaimer_popup.php'; ?>

</body>
</html>

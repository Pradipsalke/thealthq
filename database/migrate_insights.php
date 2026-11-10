<?php
/**
 * Migration: Expand Insights table to support full dynamic blog management
 */

require_once __DIR__ . '/../config/db.php';

try {
    $db = getDB();
    echo "Connected to database.\n";

    // 1. Check existing columns in `insights`
    $existingCols = [];
    $stmt = $db->query("SHOW COLUMNS FROM `insights`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingCols[] = $row['Field'];
    }

    $columnsToAdd = [
        'insight_type'    => "ALTER TABLE `insights` ADD COLUMN `insight_type` ENUM('internal', 'external') NOT NULL DEFAULT 'internal' AFTER `id`",
        'slug'            => "ALTER TABLE `insights` ADD COLUMN `slug` VARCHAR(255) DEFAULT NULL AFTER `insight_type`",
        'subtitle'        => "ALTER TABLE `insights` ADD COLUMN `subtitle` VARCHAR(500) DEFAULT NULL AFTER `title`",
        'featured_image'  => "ALTER TABLE `insights` ADD COLUMN `featured_image` VARCHAR(500) DEFAULT NULL AFTER `subtitle`",
        'author_name'     => "ALTER TABLE `insights` ADD COLUMN `author_name` VARCHAR(255) DEFAULT 'Nilesh Biniwale' AFTER `featured_image`",
        'author_role'     => "ALTER TABLE `insights` ADD COLUMN `author_role` VARCHAR(255) DEFAULT 'Managing Director, Pattern  ·  Co-author, The ALT HQ' AFTER `author_name`",
        'author_photo'    => "ALTER TABLE `insights` ADD COLUMN `author_photo` VARCHAR(500) DEFAULT 'assets/author-biniwale.jpg' AFTER `author_role`",
        'author_bio'      => "ALTER TABLE `insights` ADD COLUMN `author_bio` TEXT DEFAULT NULL AFTER `author_photo`",
        'author_linkedin' => "ALTER TABLE `insights` ADD COLUMN `author_linkedin` VARCHAR(500) DEFAULT 'https://www.linkedin.com/in/nileshbiniwale/' AFTER `author_bio`",
        'read_time'       => "ALTER TABLE `insights` ADD COLUMN `read_time` VARCHAR(50) DEFAULT '6 min read' AFTER `author_linkedin`",
        'published_date'  => "ALTER TABLE `insights` ADD COLUMN `published_date` DATE DEFAULT NULL AFTER `read_time`",
        'content'         => "ALTER TABLE `insights` ADD COLUMN `content` LONGTEXT DEFAULT NULL AFTER `published_date`",
        'seo_title'       => "ALTER TABLE `insights` ADD COLUMN `seo_title` VARCHAR(255) DEFAULT NULL AFTER `content`",
        'seo_description' => "ALTER TABLE `insights` ADD COLUMN `seo_description` TEXT DEFAULT NULL AFTER `seo_title`"
    ];

    foreach ($columnsToAdd as $colName => $alterSql) {
        if (!in_array($colName, $existingCols)) {
            $db->exec($alterSql);
            echo "Added column: {$colName}\n";
        } else {
            echo "Column already exists: {$colName}\n";
        }
    }

    // Add unique index on slug if not exists
    $indexes = [];
    $stmt = $db->query("SHOW INDEX FROM `insights`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $indexes[] = $row['Key_name'];
    }
    if (!in_array('idx_slug', $indexes)) {
        $db->exec("ALTER TABLE `insights` ADD UNIQUE KEY `idx_slug` (`slug`)");
        echo "Added unique index idx_slug\n";
    }

    // 2. Update existing records with rich content
    // Record #1: Pune's Next Leap
    $content1 = <<<HTML
<p>Pune has built a strong position on the global GCC map. Its universities, engineering heritage, talent and entrepreneurial culture have all contributed to this journey. The Maharashtra government’s focus on infrastructure and investment has created a foundation we can build on.</p>
<p>As GCC leaders, I believe our role is to work with government to shape what comes next. We understand the needs of global businesses and can bring that perspective to the table. Government brings the ability to connect institutions and enable development at scale. Together, we can help Pune grow as a centre for innovation, research and global leadership.</p>

<h2>A dedicated partnership for Pune</h2>
<p>Pune has an interesting position among India’s leading GCC destinations. Bengaluru, Hyderabad, Chennai and Mumbai are state capitals, while NCR benefits from its proximity to the national government. Pune has built its position without that same administrative proximity.</p>
<p>This creates an opportunity for a dedicated government team for Pune, working closely with GCC leadership. Such a team could bring continuity to discussions on infrastructure, talent, investment and the city’s future requirements.</p>
<p>Our responsibility as industry leaders would be to make this partnership useful. We should bring clear priorities, supporting information and a willingness to participate in solutions. A shared agenda, with regular reviews and ownership on both sides, could help us make steady progress. We have an opportunity to build on the government’s existing efforts with greater focus on Pune.</p>

<figure style="margin: 36px 0 40px; padding: 28px 32px; border-radius: 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-top: 4px solid #FFD200">
<blockquote style="margin: 0; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 22px; line-height: 1.45; color: #005B94">At The Alt HQ, I would like us to contribute to this collaboration by connecting GCC leaders, academic institutions and founders.</blockquote>
</figure>

<h2>Building a research culture around our universities</h2>
<p>Pune’s academic ecosystem is one of its biggest strengths. We have universities, research institutions and students across disciplines. GCCs already benefit from this ecosystem through recruitment. I believe we can build a much deeper relationship.</p>
<p>Some of the world’s largest technology companies started in university environments. Google grew out of research at Stanford, and Facebook began among students at Harvard. These are different stories, but both show the possibilities that emerge when young people have the environment, encouragement and freedom to build something new. Google also demonstrates how research can become the foundation of a global company.</p>
<p>Pune has the potential to create more of these opportunities. Our universities bring knowledge and curiosity. GCCs bring access to global problems, technical expertise and business understanding. Startups bring speed and the willingness to experiment. Government can help bring these groups together around a long-term agenda.</p>
<p>We should jointly support research programmes, faculty engagement, doctoral work and student projects that address meaningful problems. GCC leaders can contribute funding, mentors and opportunities to test ideas. Together with government and academia, we can build the support that allows promising research to develop into products and companies.</p>
<p>This will require patience. Research will not always produce results within a business quarter. We must be willing to support ideas whose value takes time to emerge.</p>

<p>At The Alt HQ, I would like us to contribute to this collaboration by connecting GCC leaders, academic institutions and founders. The opportunity is to build Pune’s next startup wave through research and, in the process, make our GCC ecosystem even stronger.</p>

<h2>Connecting the Maharashtra that powers Pune</h2>
<p>Pune’s talent comes from across Maharashtra. Nashik, Kolhapur, Solapur, Nagpur and many other cities and towns contribute people who build their careers here while retaining close connections with their hometowns.</p>
<p>We should see connectivity with these places as part of Pune’s growth agenda. Projects like Samruddhi Mahamarg show the ambition Maharashtra brings to infrastructure. GCC leadership can work with government to bring a talent perspective into future connectivity discussions.</p>
<p>We can contribute insights into where our people come from, how they travel and which connections would make the greatest difference. Better road, rail and bus services would help professionals maintain family connections and make opportunities in Pune more accessible.</p>
<p>Alongside transport, we should extend internships, mentoring and university partnerships across the state. Pune’s growth can create opportunities well beyond the city.</p>

<h2>Strengthening international connectivity</h2>
<p>As GCCs in Pune take on larger global responsibilities, international connectivity becomes more important. Leadership teams, customers and specialists need to travel easily, and our people need access to the markets they serve.</p>
<p>The GCC community can bring together its travel requirements and work with government, airport stakeholders and airlines to build a stronger case for more direct international connections.</p>
<p>This is an area where collective participation can make a difference. A clear picture of sustained business demand can support discussions on routes, airport capacity and ground access. Better connectivity would help Pune attract and deliver more global mandates.</p>

<h2>Building a city where people choose to stay, and return</h2>
<p>People choose a city for the life it offers their families. Schools, healthcare, housing, public spaces, arts, restaurants and nightlife all matter. Pune has a strong cultural identity, and we can build on it to create an even more vibrant and welcoming environment.</p>
<p>There is an opportunity to reconnect with professionals from Pune and Maharashtra who moved overseas, including during the technology boom of the late 1990s. Many have since built substantial careers and bring valuable leadership experience, specialist knowledge and global networks.</p>
<p>Government and Pune’s GCC community can jointly create opportunities for them to participate in the city’s future. They could invest in startups, mentor founders, collaborate with universities or explore leadership roles here.</p>
<p>We could build a network that connects these professionals with opportunities. Participation can start with a mentoring conversation, a research partnership or an investment. Over time, some may choose to return.</p>
<p>For those considering that move, GCC leaders can create meaningful roles with global responsibility. Industry and government can work together to make Pune an attractive choice for their families. Returning home should feel like an exciting next step in their careers.</p>

<h2>Building Pune’s next chapter together</h2>
<p>Pune’s next chapter will be shaped by how well we work together. Government, GCCs, universities and startups each bring something important. Our opportunity is to connect these strengths and stay committed beyond individual projects.</p>
<p>As GCC leaders, we should bring our time, experience and relationships to this effort. Together, we can build a Pune that develops talent, creates companies, welcomes returning leaders and contributes more to Maharashtra’s growth.</p>
HTML;

    $stmt = $db->prepare("UPDATE `insights` SET 
        `insight_type` = 'internal',
        `slug` = 'punes-next-leap',
        `subtitle` = 'GCC Leaders and Government Building a Global Innovation Hub',
        `link_url` = 'insight.php?slug=punes-next-leap',
        `is_external` = 0,
        `author_name` = 'Nilesh Biniwale',
        `author_role` = 'Managing Director, Pattern  ·  Co-author, The ALT HQ',
        `author_photo` = 'assets/author-biniwale.jpg',
        `author_bio` = 'Managing Director of Pattern India and co-author of The ALT HQ. He built Pattern’s GCC from scratch to a team of almost 1,000.',
        `author_linkedin` = 'https://www.linkedin.com/in/nileshbiniwale/',
        `read_time` = '6 min read',
        `published_date` = '2026-03-01',
        `content` = ?,
        `seo_title` = 'Pune’s Next Leap',
        `seo_description` = 'Nilesh Biniwale on how GCC leaders and government can build Pune into a global innovation hub.'
        WHERE `id` = 1");
    $stmt->execute([$content1]);
    echo "Updated insight #1 (Pune's Next Leap)\n";

    // Record #2: Beyond the execution model (External)
    $stmt = $db->prepare("UPDATE `insights` SET 
        `insight_type` = 'external',
        `slug` = 'beyond-execution-model',
        `subtitle` = 'Global capability centers: Driving innovation and growth',
        `link_url` = 'https://www.thoughtworks.com/perspectives/edition37-global-capability-centers/article',
        `is_external` = 1,
        `author_name` = 'Nilesh Kulkarni',
        `author_role` = 'Co-author, The ALT HQ',
        `author_photo` = 'assets/author-kulkarni.jpg',
        `author_bio` = 'Co-author of The ALT HQ.',
        `author_linkedin` = 'https://www.linkedin.com/in/nileshrk/',
        `read_time` = 'Thoughtworks article',
        `published_date` = '2026-02-15',
        `content` = NULL,
        `seo_title` = 'Beyond the execution model | Thoughtworks Perspectives',
        `seo_description` = 'Nilesh Kulkarni on hiring for the right mandate, building GCC leadership and measuring the value a centre delivers.'
        WHERE `id` = 2");
    $stmt->execute();
    echo "Updated insight #2 (Thoughtworks Perspectives - External)\n";

    // Record #3: From Triangle to Apple to Hourglass
    $content3 = <<<HTML
<p>An organisation’s shape tells us where talent enters, how capability develops and where decisions get made. The familiar triangle, the distorted apple and the emerging hourglass each tell a different story. For leaders of Global Capability Centres, understanding these shapes is a practical responsibility. Each structure influences cost, execution, innovation and the development of future leaders. As businesses examine management layers and introduce AI into workflows, the central question is whether they are improving the flow of talent or simply changing the silhouette.</p>
<p>The book’s discussion of the talent pyramid begins with three groups: Feeders, Stabilizers and Differentiators. These describe contributions to organisational capability, rather than merely job titles. Feeders bring curiosity and fresh thinking. Stabilizers provide experience, discipline and dependable delivery. Differentiators translate strategic ambition into execution and create exceptional impact. A healthy organisation needs all three. Its strength comes from how effectively they work together and how deliberately people are developed to take on greater responsibility.</p>
<p>In the triangular model, a broad base of Feeders creates a continuing supply of emerging talent. These professionals need meaningful problems, ownership and access to experienced colleagues. Their questions can challenge practices that others have stopped examining. The middle layer of Stabilizers evaluates ideas, manages risks and makes promising approaches repeatable. At the top, a smaller group of Differentiators provides direction, expertise and accountability. The book argues that this balance should exist within each value stream, making talent development part of everyday delivery.</p>
<p>The triangle is therefore more than a staffing ratio. It is a development system. Feeders build judgement through practice. Stabilizers deepen their expertise and prepare for broader influence. Differentiators create opportunities for others while delivering business outcomes. Progression should reflect demonstrated capability and potential. When organisations reward tenure without examining contribution, or hire senior people without testing their ability to deliver, this system begins to weaken. The reporting chart may still look triangular even while the underlying talent pyramid is losing its strength.</p>
<figure style="margin: 36px 0 40px">
<img src="assets/shapes-diagram.jpg" alt="The changing shape of organisations: the triangle as a healthy talent pipeline, the apple as a talent imbalance, and the hourglass as a proposed model for the AI era with context providers above, executors below and AI-supported coordination at the waist" style="width: 100%; height: auto; display: block; border-radius: 12px; border: 1px solid #E2E8F0">
<figcaption style="margin-top: 12px; font-size: 14px; line-height: 1.5; color: #4A5568">Triangle and apple: book framework. Hourglass: proposed extension for the AI era.</figcaption>
</figure>
<h2>The apple: a warning sign</h2>
<p>This is where the book introduces the apple-shaped pyramid as a warning. The base narrows because too few early-career professionals are hired or empowered. The middle expands as people accumulate without sufficient development, movement or differentiation in performance. The top loses effectiveness when senior appointments fail to produce the expected impact. The apple describes an imbalance in talent quality and renewal. A large population of experienced professionals alone does not establish the problem; the concern is a widening gap between cost and contribution.</p>
<p>The consequences can reinforce one another. Limited opportunities discourage ambitious employees. Slow decisions make experimentation harder. Experienced colleagues spend more time protecting established routines and less time improving them. High-potential talent may leave when advancement appears disconnected from performance. Meanwhile, a weak intake of Feeders reduces the organisation’s capacity to renew itself. The book’s challenge to leaders is clear: identify potential, develop it and create conditions in which stronger contribution can grow. Leaving the middle unchanged can allow stagnation to become accepted practice.</p>
<h2>The hourglass: a proposal for the AI era</h2>
<p>Building on this framework, I see a reason for the hourglass after the advent of AI: organisations will continue to need context providers and executors, while some coordination work between them can shrink. The upper half holds people who understand customers, business priorities, domain constraints and risk. The lower half holds people who turn that direction into reliable outcomes. AI can help connect the two by supporting analysis, planning and information flow. This is a possible organisational design, rather than a universal prediction about headcount.</p>
<p>Context providers determine which problems deserve attention, what success means and which tradeoffs are acceptable. Their value lies in combining business understanding with judgement and accountability. Executors apply expertise, use AI tools, validate outputs and deliver results in real operating conditions. They include experienced practitioners as well as emerging talent. These contributions can overlap within one person or team. The hourglass therefore describes where value concentrates: in setting meaningful direction and making it work. Neither end can function effectively without feedback from the other.</p>
<h2>A thinner middle. A stronger connection.</h2>
<p>The middle narrows where its main purpose is passing instructions, consolidating updates or coordinating predictable workflows that AI can increasingly support. Yet Stabilizers and middle managers are not interchangeable. An experienced engineer who protects quality remains essential even if a reporting layer disappears. Coaching, resolving conflict and handling exceptions also need clear ownership. The opportunity is to redeploy capable people towards context, execution and development. Removing positions without assigning their essential responsibilities risks creating a bottleneck at the waist of the hourglass.</p>
<p>A narrow middle can also interrupt the development of future leaders. Junior professionals need feedback, examples of sound judgement and opportunities to learn from mistakes. Senior leaders cannot assume that direct access to them replaces regular coaching. An hourglass structure needs deliberate connections across its waist: mentoring, technical communities, project leadership and clear escalation routes. It also needs credible specialist careers. Employees should be able to gain recognition, responsibility and compensation through expertise without having to build a team beneath them.</p>
<figure style="margin: 32px 0 36px; padding: 28px 32px; border-radius: 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-top: 4px solid #FFD200">
<blockquote style="margin: 0; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 22px; line-height: 1.45; color: #005B94">Preserve coaching, domain expertise and accountability across every shape.</blockquote>
</figure>
<h2>What GCC leaders can do now</h2>
<p>For GCC leaders, the practical response starts with assessing each value stream. Is there a healthy flow of Feeders? Are Stabilizers improving delivery and developing others? Are Differentiators producing the impact their roles require? Use these questions to guide hiring, development and succession. Address persistent performance gaps with clear expectations, support and fair decisions. Remove duplicated approvals while protecting domain knowledge. Measure decision speed, quality, customer outcomes and internal progression alongside cost. This creates a fuller picture of whether a restructuring is strengthening the organisation.</p>
<p>The triangle offers a foundation for talent renewal. The apple warns of stagnation when development and performance lose momentum. The hourglass adds a new proposition for the AI era: value can concentrate around those who provide context and those who execute, with fewer layers needed to connect them. Its success depends on preserving judgement, expertise and learning across that narrower middle. I believe leaders should build towards this deliberately, helping people strengthen their contribution at either end while maintaining the connections that turn business intent into dependable outcomes and sustain future talent development.</p>
HTML;

    $stmt = $db->prepare("UPDATE `insights` SET 
        `insight_type` = 'internal',
        `slug` = 'triangle-apple-hourglass',
        `subtitle` = 'The Organisation in the AI Era',
        `featured_image` = 'assets/shapes-diagram.jpg',
        `link_url` = 'insight.php?slug=triangle-apple-hourglass',
        `is_external` = 0,
        `author_name` = 'Nilesh Biniwale',
        `author_role` = 'Managing Director, Pattern  ·  Co-author, The ALT HQ',
        `author_photo` = 'assets/author-biniwale.jpg',
        `author_bio` = 'Managing Director of Pattern India and co-author of The ALT HQ. He built Pattern’s GCC from scratch to a team of almost 1,000.',
        `author_linkedin` = 'https://www.linkedin.com/in/nileshbiniwale/',
        `read_time` = '7 min read',
        `published_date` = '2026-03-05',
        `content` = ?,
        `seo_title` = 'From Triangle to Apple to Hourglass',
        `seo_description` = 'Nilesh Biniwale on how organisational shape is changing in the AI era.'
        WHERE `id` = 3");
    $stmt->execute([$content3]);
    echo "Updated insight #3 (From Triangle to Apple to Hourglass)\n";

    // Check if #4 (In talent, shape matters) exists
    $count = (int)$db->query("SELECT COUNT(*) FROM `insights` WHERE `slug` = 'talent-shape-matters'")->fetchColumn();
    if ($count === 0) {
        $content4 = <<<HTML
<p>“To really nurture talent and skill development,” says Nilesh Biniwale, “we need to understand the ‘Talent Pyramid’. It’s not just a nice diagram. It’s the foundation of how successful organisations actually build their capabilities.”</p>
<p>Nilesh Kulkarni agrees wholeheartedly: “The classic talent pyramid needs to be used for overall talent strategy. We’ve seen too many GCCs ignore this model and pay the price later.”</p>
<p>In building a sustainable and high-performing organisation, especially within GCCs, talent strategy must be approached with as much discipline and clarity as product strategy. One timeless model that still holds immense value is the classic talent pyramid, but only when implemented thoughtfully.</p>
<p>At the base of this pyramid are the <strong>Feeders</strong>: early-career professionals, often from campuses or finishing schools, who bring fresh energy, curiosity, and disruptive thinking. They operate at a lower salary band but are a vital source of innovation. Empowering this group, giving them ownership and real-world problems to solve, accelerates not just delivery but also cultural vitality. This layer challenges the norm and keeps the system dynamic.</p>
<p>Next come the <strong>Stabilizers</strong>: mid-level professionals with experience and maturity. They don’t just follow processes. They enforce them, refine them, and act as the organisation’s shock absorbers. When Feeders propose bold ideas, Stabilizers assess the risk, validate feasibility, and ensure responsible execution. Their role is to institutionalise what’s promising and protect the organisation from what’s not. They strengthen the core.</p>
<p>At the top of the pyramid are the <strong>Differentiators</strong>: strategic drivers who are few in number but critical in impact. They break down macro objectives into micro plans. They turn vision into executable tasks. Often more expensive, they’re expected to deliver exponentially higher value.</p>
<p>Every value stream in an organisation should reflect this triangular talent structure. Overlaying this, there exists another dimension, performance and potential: low, mid, and high. Ideally, the Differentiator layer should only contain high-potential and high-performing individuals. But mistakes happen, and sometimes, external hires who haven’t been rigorously evaluated enter this layer and dilute its quality.</p>
<p>This misalignment is where problems begin; and over time, if left unchecked, leads to a dangerous organisational pattern: the apple-shaped talent pyramid.</p>
<h2>The apple-shaped pyramid: a silent regressor</h2>
<p>When organisations fail to nurture high potential talent, and instead accumulate average performers, the healthy pyramid begins to distort:</p>
<p><strong>The base narrows</strong> as fewer Feeders are hired or empowered. Innovation dries up. The talent supply chain weakens.</p>
<p><strong>The middle bulges</strong> with an excess of Stabilizers, many of whom are average performers. This is the “apple” part, fat in the centre, where career growth stalls, energy dips, and risk aversion sets in.</p>
<p><strong>The top gets diluted</strong>, with Differentiators who may be misaligned or not high-performing, often due to flawed hiring or internal misjudgements.</p>
<p>In such a model, costs rise; tenure increases compensation but not impact. Decision-making slows. Fresh ideas struggle to reach execution. High-potential employees feel stifled and leave. Bottom performers are eventually let go. But what’s left behind is a bloated middle layer that defines the new normal. Mediocrity becomes the standard.</p>
<figure style="margin: 32px 0 36px; padding: 28px 32px; border-radius: 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-top: 4px solid #FFD200">
<blockquote style="margin: 0; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 22px; line-height: 1.45; color: #005B94">The greatest threat to a high-performance culture isn’t low performers, it’s the entrenchment of average talent in key layers.</blockquote>
</figure>
<p>The greatest threat to a high-performance culture isn’t low performers, it’s the entrenchment of average talent in key layers. Over time, this drives the organisation from excellence to stagnation.</p>
<h2>Breaking the apple shape: a call for talent precision</h2>
<p>To fix this drift, organisations must act with intent:</p>
<h3 style="margin: 28px 0 10px; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 20px; color: #1E2229">Revitalise the base (Feeders)</h3>
<ul style="margin: 0 0 22px; padding-left: 22px; font-size: 18px; line-height: 1.7; color: #1E2229"><li style="margin-bottom: 6px">Build strong university and early-career programmes.</li><li style="margin-bottom: 6px">Create real ownership paths for young talent.</li><li style="margin-bottom: 6px">Recognise innovation from the bottom, not just the top.</li></ul>
<h3 style="margin: 28px 0 10px; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 20px; color: #1E2229">Right-size the middle (Stabilizers)</h3>
<ul style="margin: 0 0 22px; padding-left: 22px; font-size: 18px; line-height: 1.7; color: #1E2229"><li style="margin-bottom: 6px">Differentiate between average and emerging leaders.</li><li style="margin-bottom: 6px">Run upskilling or re-deployment programmes for the willing.</li><li style="margin-bottom: 6px">Create exit paths for persistent underperformers and mediocre performers.</li></ul>
<h3 style="margin: 28px 0 10px; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 20px; color: #1E2229">Sharpen the top (Differentiators)</h3>
<ul style="margin: 0 0 22px; padding-left: 22px; font-size: 18px; line-height: 1.7; color: #1E2229"><li style="margin-bottom: 6px">Promote based on capability, not comfort or politics.</li><li style="margin-bottom: 6px">Provide Differentiators with cross-functional stretch roles.</li><li style="margin-bottom: 6px">Ensure accountability, not just compensation.</li></ul>
<h3 style="margin: 28px 0 10px; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 20px; color: #1E2229">Measure the shape, not just the score</h3>
<ul style="margin: 0 0 22px; padding-left: 22px; font-size: 18px; line-height: 1.7; color: #1E2229"><li style="margin-bottom: 6px">Conduct regular talent health diagnostics.</li><li style="margin-bottom: 6px">Review potential and performance matrices as rigorously as financial reports.</li><li style="margin-bottom: 6px">Challenge whether the pyramid is still a pyramid, or if it’s slowly becoming an apple.</li></ul>
<h2>Final thought</h2>
<p>Talent management is not just about removing the weakest links. It’s about identifying the strongest, and building systems that enable them to rise. Inaction on top talent is more dangerous than the presence of low performers.</p>
<p>As leaders, we must teach our managers to spot potential, invest in it, and avoid the comfort trap of mediocrity. GCCs, in particular, cannot afford an apple-shaped structure. They must stay lean at the top, bold at the base, and agile in the middle.</p>
<p><strong>In talent, shape matters.</strong> And the healthiest shape is one that grows from the bottom, fortifies the middle, and sharpens the top.</p>
HTML;

        $stmt = $db->prepare("INSERT INTO `insights` (
            `insight_type`, `slug`, `title`, `subtitle`, `top_badge`, `category`, `headline`, `description`,
            `link_url`, `link_text`, `is_external`, `banner_bg`, `banner_text_color`, `badge_color`,
            `display_order`, `is_active`, `author_name`, `author_role`, `author_photo`, `author_bio`,
            `author_linkedin`, `read_time`, `published_date`, `content`, `seo_title`, `seo_description`
        ) VALUES (
            'internal', 'talent-shape-matters', 'In talent, shape matters', 'Rethinking the talent pyramid and the hidden cost of mediocrity',
            'From the book  |  Chapter 3', 'Talent and Leadership', 'Rethinking the talent pyramid and the hidden cost of mediocrity',
            'Nilesh Biniwale and Nilesh Kulkarni  ·  4 min read',
            'insight.php?slug=talent-shape-matters', 'Read the excerpt →', 0, '#0077B3', '#FFFFFF', '#FFD200',
            4, 1, 'Nilesh Biniwale and Nilesh Kulkarni', 'Authors of The ALT HQ', 'assets/author-biniwale.jpg',
            'Nilesh Biniwale is MD of Pattern India. Nilesh Kulkarni is a senior technology leader and advisor.',
            'https://www.linkedin.com/in/nileshbiniwale/', '4 min read', '2026-02-01', ?, 'In talent, shape matters',
            'An excerpt from The ALT HQ on the talent pyramid and the hidden cost of the apple-shaped organisation.'
        )");
        $stmt->execute([$content4]);
        echo "Inserted insight #4 (In talent, shape matters)\n";
    }

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}

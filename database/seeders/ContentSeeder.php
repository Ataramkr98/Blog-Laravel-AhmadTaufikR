<?php

namespace Database\Seeders;

use App\Actions\SyncCourseContent;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Citation;
use App\Models\ContentReview;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\FinalExamAttempt;
use App\Models\Glossary;
use App\Models\LessonProgress;
use App\Models\MediaAsset;
use App\Models\NewsletterSubscription;
use App\Models\QuizQuestion;
use App\Models\SystemSetting;
use App\Models\Tag;
use App\Models\User;
use App\Support\CoverImageGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = $this->seedUsers();
            $this->seedSystemSettings();
            $categories = $this->seedCategories();
            $tags = $this->seedTags();
            $glossaries = $this->seedGlossaries();
            $articles = $this->seedArticles($admin, $categories, $tags, $glossaries);
            $this->seedCitations($articles);
            $this->seedQuizQuestions($articles);
            $this->seedContentReviews($admin, $articles);
            $this->seedArticleViews($articles);
            $this->seedCourses($admin, $articles);
            $this->seedEnrollmentsAndProgress($admin);
            $this->seedNewsletterSubscriptions();
        });
    }

    private function seedUsers(): User
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@biology.learn'],
            [
                'name' => 'Dr. Sarah Stone',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'academic_level' => 'pt',
                'email_verified_at' => now(),
            ],
        );

        User::firstOrCreate(
            ['email' => 'penulis@biology.learn'],
            [
                'name' => 'Prof. Edwin Santoso',
                'password' => bcrypt('password'),
                'role' => 'penulis',
                'academic_level' => 'pt',
                'email_verified_at' => now(),
            ],
        );

        // Sample reader accounts that the LMS demo can attach enrollments
        // and progress to without depending on real signups.
        $samples = [
            ['email' => 'andini@biology.learn', 'name' => 'Andini Pratiwi', 'academic_level' => 'sma'],
            ['email' => 'bram@biology.learn', 'name' => 'Bram Hartono', 'academic_level' => 'pt'],
            ['email' => 'citra@biology.learn', 'name' => 'Citra Dewi', 'academic_level' => 'sma'],
            ['email' => 'farhan@biology.learn', 'name' => 'Farhan Ramadhan', 'academic_level' => 'pt'],
        ];

        foreach ($samples as $sample) {
            User::firstOrCreate(
                ['email' => $sample['email']],
                [
                    'name' => $sample['name'],
                    'password' => bcrypt('password'),
                    'role' => 'pembaca',
                    'academic_level' => $sample['academic_level'],
                    'email_verified_at' => now(),
                ],
            );
        }

        $this->seedUserAvatar($admin, 'dr-sarah-stone.webp');

        return $admin;
    }

    private function seedUserAvatar(User $user, string $filename): MediaAsset
    {
        $sourcePath = database_path('seeders/assets/users/'.$filename);

        if (! is_file($sourcePath)) {
            throw new RuntimeException("Foto profil user seed tidak ditemukan: {$filename}");
        }

        $imageInfo = getimagesize($sourcePath);

        if ($imageInfo === false
            || $imageInfo[0] !== 512
            || $imageInfo[1] !== 512
            || $imageInfo[2] !== IMAGETYPE_WEBP) {
            throw new RuntimeException("Foto profil user seed harus berupa WebP 512x512: {$filename}");
        }

        $contents = file_get_contents($sourcePath);

        if (! is_string($contents)) {
            throw new RuntimeException("Foto profil user seed tidak dapat dibaca: {$filename}");
        }

        $storagePath = 'users/seed/'.$filename;

        if (! Storage::disk('public')->put($storagePath, $contents)) {
            throw new RuntimeException("Foto profil user seed tidak dapat disimpan: {$filename}");
        }

        $asset = MediaAsset::query()->updateOrCreate(
            ['path' => $storagePath],
            [
                'user_id' => $user->id,
                'original_name' => $filename,
                'disk' => 'public',
                'mime_type' => 'image/webp',
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'size' => strlen($contents),
            ],
        );

        if ($this->shouldUseSeededUserAvatar($user->getRawOriginal('avatar_url'))) {
            $user->update(['avatar_url' => $asset->url]);
        }

        return $asset;
    }

    private function shouldUseSeededUserAvatar(?string $avatarUrl): bool
    {
        if (blank($avatarUrl)) {
            return true;
        }

        $seededAvatarPrefix = rtrim(Storage::disk('public')->url('users/seed'), '/').'/';

        return Str::startsWith($avatarUrl, $seededAvatarPrefix)
            || Str::startsWith($avatarUrl, '/storage/users/seed/');
    }

    private function seedSystemSettings(): void
    {
        SystemSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'maintenance_mode' => false,
                'review_notifications' => true,
                'glossary_cache_minutes' => 60,
            ],
        );
    }

    /**
     * @return Collection<string, Category> keyed by slug
     */
    private function seedCategories(): Collection
    {
        $definitions = [
            ['name' => 'Fisiologi Sel', 'slug' => 'fisiologi-sel', 'level' => 'sma', 'sort_order' => 10, 'description' => 'Materi seluler untuk siswa SMA.'],
            ['name' => 'Genetika Molekuler', 'slug' => 'genetika-molekuler', 'level' => 'pt', 'sort_order' => 20, 'description' => 'Materi genetika tingkat perguruan tinggi.'],
            ['name' => 'Ekologi Lingkungan', 'slug' => 'ekologi-lingkungan', 'level' => 'sma', 'sort_order' => 30, 'description' => 'Materi ekosistem dan interaksi organisme.'],
            ['name' => 'Biokimia Enzim', 'slug' => 'biokimia-enzim', 'level' => 'pt', 'sort_order' => 40, 'description' => 'Materi biokimia lanjut.'],
            ['name' => 'Anatomi & Fisiologi', 'slug' => 'anatomi-fisiologi', 'level' => 'sma', 'sort_order' => 50, 'description' => 'Struktur tubuh organisme dan cara kerjanya.'],
            ['name' => 'Bioteknologi', 'slug' => 'bioteknologi', 'level' => 'pt', 'sort_order' => 60, 'description' => 'Aplikasi biologi molekuler dalam industri dan kedokteran.'],
        ];

        return collect($definitions)
            ->mapWithKeys(function (array $category): array {
                $model = Category::firstOrCreate(['slug' => $category['slug']], $category);

                return [$category['slug'] => $model];
            });
    }

    /**
     * @return Collection<string, Tag> keyed by slug
     */
    private function seedTags(): Collection
    {
        $names = [
            'Mitosis',
            'DNA',
            'Ekosistem',
            'Enzim',
            'Membran Sel',
            'Sitologi',
            'Siklus Sel',
            'Metabolisme',
            'Respirasi Seluler',
            'Fotosintesis',
            'Evolusi',
            'Bioinformatika',
            'PCR',
            'Ekspresi Gen',
        ];

        return collect($names)
            ->mapWithKeys(function (string $name): array {
                $slug = Str::slug($name);
                $tag = Tag::firstOrCreate(['slug' => $slug], ['name' => $name]);

                return [$slug => $tag];
            });
    }

    /**
     * @return Collection<string, Glossary> keyed by term
     */
    private function seedGlossaries(): Collection
    {
        $definitions = [
            ['term' => 'Panthera leo', 'common_name' => 'Singa', 'definition' => 'Mamalia karnivora besar dari keluarga Felidae.', 'level' => 'sma', 'category' => 'Taksonomi'],
            ['term' => 'Homo sapiens', 'common_name' => 'Manusia', 'definition' => 'Spesies hominid yang masih hidup.', 'level' => 'pt', 'category' => 'Taksonomi'],
            ['term' => 'Escherichia coli', 'common_name' => 'E. Coli', 'definition' => 'Bakteri gram-negatif berbentuk basil yang umum ditemukan di usus.', 'level' => 'pt', 'category' => 'Mikrobiologi'],
            ['term' => 'Na+/K+-ATPase', 'common_name' => 'Pompa Natrium Kalium', 'definition' => 'Protein transmembran yang menggunakan ATP untuk mempertahankan gradien ion natrium dan kalium.', 'level' => 'sma', 'category' => 'Fisiologi Sel'],
            ['term' => 'CRISPR-Cas9', 'common_name' => 'Sistem Penyunting Gen', 'definition' => 'Sistem pertahanan bakteri yang diadaptasi untuk menyunting DNA secara terarah.', 'level' => 'pt', 'category' => 'Genetika Molekuler'],
            ['term' => 'Mitosis', 'common_name' => 'Pembelahan Sel', 'definition' => 'Proses pembelahan inti sel eukariotik menjadi dua anak inti yang identik secara genetik.', 'level' => 'sma', 'category' => 'Sitologi'],
            ['term' => 'Mitokondria', 'common_name' => 'Pembangkit Energi Sel', 'definition' => 'Organel bermembran ganda tempat berlangsungnya fosforilasi oksidatif dan sintesis ATP.', 'level' => 'pt', 'category' => 'Biokimia'],
            ['term' => 'ATP', 'common_name' => 'Adenosin Trifosfat', 'definition' => 'Molekul pembawa energi utama pada reaksi metabolisme sel.', 'level' => 'sma', 'category' => 'Biokimia'],
            ['term' => 'Katalis', 'common_name' => 'Katalisator', 'definition' => 'Zat yang mempercepat laju reaksi tanpa ikut terpakai pada reaksi.', 'level' => 'sma', 'category' => 'Kimia'],
            ['term' => 'Konsentrasi Substrat', 'common_name' => '[S]', 'definition' => 'Jumlah substrat yang tersedia di lingkungan reaksi enzimatis pada satuan waktu tertentu.', 'level' => 'pt', 'category' => 'Biokimia'],
            ['term' => 'Zooxanthellae', 'common_name' => 'Alga Simbion Karang', 'definition' => 'Alga dinoflagelata simbion pada jaringan koral yang menyediakan hasil fotosintesis.', 'level' => 'sma', 'category' => 'Ekologi'],
            ['term' => 'Fotosintesis', 'common_name' => 'Proses Penyerapan Cahaya', 'definition' => 'Proses konversi energi cahaya menjadi energi kimia oleh organisme fotoautotrof menggunakan pigmen klorofil di dalam kloroplas.', 'level' => 'sma', 'category' => 'Biokimia'],
            ['term' => 'Klorofil', 'common_name' => 'Pigmen Hijau Daun', 'definition' => 'Pigmen hijau yang terdapat dalam kloroplas, berperan utama dalam menyerap cahaya merah dan biru untuk fotosintesis.', 'level' => 'sma', 'category' => 'Biokimia'],
            ['term' => 'PCR', 'common_name' => 'Polymerase Chain Reaction', 'definition' => 'Teknik amplifikasi fragmen DNA secara in vitro menggunakan enzim DNA polimerase tahan panas (Taq polymerase) dalam siklus denaturasi-annealing-ekstensi.', 'level' => 'pt', 'category' => 'Biologi Molekuler'],
            ['term' => 'Operon', 'common_name' => 'Unit Regulasi Gen', 'definition' => 'Unit transkripsi pada prokariot yang terdiri dari promotor, operator, dan gen struktural yang diregulasi bersama sebagai satu kesatuan fungsional.', 'level' => 'pt', 'category' => 'Genetika Molekuler'],
            ['term' => 'Seleksi Alam', 'common_name' => 'Natural Selection', 'definition' => 'Mekanisme evolusi di mana organisme dengan sifat fenotipe yang lebih adaptif terhadap lingkungan memiliki peluang bertahan hidup dan bereproduksi lebih tinggi.', 'level' => 'sma', 'category' => 'Evolusi'],
        ];

        return collect($definitions)
            ->mapWithKeys(function (array $glossary): array {
                $model = Glossary::firstOrCreate(['term' => $glossary['term']], $glossary);

                return [$glossary['term'] => $model];
            });
    }

    /**
     * @param  Collection<string, Category>  $categories
     * @param  Collection<string, Tag>  $tags
     * @param  Collection<string, Glossary>  $glossaries
     * @return Collection<string, Article> keyed by slug
     */
    private function seedArticles(User $admin, Collection $categories, Collection $tags, Collection $glossaries): Collection
    {
        $articles = [
            // ── Existing 6 articles ──────────────────────────────────────
            [
                'title' => 'Mekanisme Transport Aktif Pompa Natrium-Kalium pada Membran Sel',
                'slug' => 'mekanisme-transport-aktif-pompa-natrium-kalium-pada-membran-sel',
                'category' => $categories['fisiologi-sel'],
                'excerpt' => 'Pelajari cara kerja transpor aktif primer melalui pompa Na+/K+-ATPase pada membran sel.',
                'content' => $this->articleContentMembrane(),
                'tags' => [$tags['membran-sel'], $tags['mitosis']],
                'glossaries' => [$glossaries['Na+/K+-ATPase']->id, $glossaries['ATP']->id],
                'image' => 'mekanisme-transport-aktif-pompa-natrium-kalium-pada-membran-sel.webp',
                'days_ago' => 1,
                'view_count' => 2777,
            ],
            [
                'title' => 'Modifikasi Genom CRISPR-Cas9 dan Implikasinya dalam Terapi Gen',
                'slug' => 'modifikasi-genom-crispr-cas9-dan-implikasinya-dalam-terapi-gen',
                'category' => $categories['genetika-molekuler'],
                'excerpt' => 'Mengenal mekanisme CRISPR-Cas9 sebagai alat rekayasa genom modern.',
                'content' => $this->articleContentCrispr(),
                'tags' => [$tags['dna']],
                'glossaries' => [$glossaries['CRISPR-Cas9']->id, $glossaries['Homo sapiens']->id],
                'image' => 'modifikasi-genom-crispr-cas9-dan-implikasinya-dalam-terapi-gen.webp',
                'days_ago' => 3,
                'view_count' => 1102,
            ],
            [
                'title' => 'Interaksi Simbiosis Mutualisme dan Parasitisme dalam Ekosistem Terumbu Karang',
                'slug' => 'interaksi-simbiosis-mutualisme-dan-parasitisme-dalam-ekosistem-terumbu-karang',
                'category' => $categories['ekologi-lingkungan'],
                'excerpt' => 'Memahami hubungan simbiosis yang menjaga keseimbangan ekosistem terumbu karang.',
                'content' => $this->articleContentCoral(),
                'tags' => [$tags['ekosistem']],
                'glossaries' => [$glossaries['Zooxanthellae']->id],
                'image' => 'interaksi-simbiosis-mutualisme-dan-parasitisme-dalam-ekosistem-terumbu-karang.webp',
                'days_ago' => 5,
                'view_count' => 1188,
            ],
            [
                'title' => 'Kinetika Enzim dan Regulasi Aktivitas Katalitik',
                'slug' => 'kinetika-enzim-dan-regulasi-aktivitas-katalitik',
                'category' => $categories['biokimia-enzim'],
                'excerpt' => 'Mempelajari laju reaksi enzimatik dan faktor yang memengaruhi aktivitas katalitik.',
                'content' => $this->articleContentEnzyme(),
                'tags' => [$tags['enzim']],
                'glossaries' => [$glossaries['Katalis']->id, $glossaries['Konsentrasi Substrat']->id, $glossaries['ATP']->id],
                'image' => 'kinetika-enzim-dan-regulasi-aktivitas-katalitik.webp',
                'days_ago' => 7,
                'view_count' => 4965,
            ],
            [
                'title' => 'Tahapan Mitosis dan Kontrol Siklus Sel pada Makhluk Eukariotik',
                'slug' => 'tahapan-mitosis-dan-kontrol-siklus-sel-pada-makhluk-eukariotik',
                'category' => $categories['anatomi-fisiologi'],
                'excerpt' => 'Mengenal fase profase, metafase, anafase, telofase, serta peran checkpoint siklus sel.',
                'content' => $this->articleContentMitosis(),
                'tags' => [$tags['mitosis'], $tags['siklus-sel'], $tags['sitologi']],
                'glossaries' => [$glossaries['Mitosis']->id, $glossaries['ATP']->id],
                'image' => 'tahapan-mitosis-dan-kontrol-siklus-sel-pada-makhluk-eukariotik.webp',
                'days_ago' => 10,
                'view_count' => 1820,
            ],
            [
                'title' => 'Mitokondria dan Sintesis ATP dalam Respirasi Seluler Aerobik',
                'slug' => 'mitokondria-dan-sintesis-atp-dalam-respirasi-seluler-aerobik',
                'category' => $categories['biokimia-enzim'],
                'excerpt' => 'Menelusuri rantai transpor elektron, gradien proton, dan peran ATP sintase.',
                'content' => $this->articleContentMitochondria(),
                'tags' => [$tags['metabolisme'], $tags['respirasi-seluler'], $tags['enzim']],
                'glossaries' => [$glossaries['Mitokondria']->id, $glossaries['ATP']->id, $glossaries['Escherichia coli']->id],
                'image' => 'mitokondria-dan-sintesis-atp-dalam-respirasi-seluler-aerobik.webp',
                'days_ago' => 12,
                'view_count' => 2334,
            ],

            // ── 6 new articles ───────────────────────────────────────────
            [
                'title' => 'Fotosintesis: Reaksi Terang dan Siklus Calvin pada Tumbuhan',
                'slug' => 'fotosintesis-reaksi-terang-dan-siklus-calvin-pada-tumbuhan',
                'category' => $categories['fisiologi-sel'],
                'excerpt' => 'Memahami dua tahap utama fotosintesis yang mengubah energi cahaya menjadi glukosa di dalam kloroplas.',
                'content' => $this->articleContentPhotosynthesis(),
                'tags' => [$tags['fotosintesis'], $tags['metabolisme']],
                'glossaries' => [$glossaries['Fotosintesis']->id, $glossaries['Klorofil']->id, $glossaries['ATP']->id],
                'image' => 'fotosintesis-reaksi-terang-dan-siklus-calvin-pada-tumbuhan.webp',
                'days_ago' => 2,
                'view_count' => 3412,
            ],
            [
                'title' => 'Struktur dan Fungsi DNA serta Proses Replikasi Semikonservatif',
                'slug' => 'struktur-dan-fungsi-dna-serta-proses-replikasi-semikonservatif',
                'category' => $categories['anatomi-fisiologi'],
                'excerpt' => 'Mengenal model double helix DNA dan mekanisme replikasi yang menjamin keutuhan informasi genetik.',
                'content' => $this->articleContentDnaReplication(),
                'tags' => [$tags['dna'], $tags['siklus-sel']],
                'glossaries' => [$glossaries['Mitosis']->id],
                'image' => 'struktur-dan-fungsi-dna-serta-proses-replikasi-semikonservatif.webp',
                'days_ago' => 4,
                'view_count' => 2156,
            ],
            [
                'title' => 'Evolusi dan Seleksi Alam menurut Teori Darwin',
                'slug' => 'evolusi-dan-seleksi-alam-menurut-teori-darwin',
                'category' => $categories['ekologi-lingkungan'],
                'excerpt' => 'Menelusuri bukti evolusi dan mekanisme seleksi alam sebagai penggerak keanekaragaman hayati.',
                'content' => $this->articleContentEvolution(),
                'tags' => [$tags['evolusi'], $tags['ekosistem']],
                'glossaries' => [$glossaries['Seleksi Alam']->id, $glossaries['Panthera leo']->id, $glossaries['Homo sapiens']->id],
                'image' => 'evolusi-dan-seleksi-alam-menurut-teori-darwin.webp',
                'days_ago' => 6,
                'view_count' => 1890,
            ],
            [
                'title' => 'Regulasi Ekspresi Gen pada Prokariot dan Eukariot',
                'slug' => 'regulasi-ekspresi-gen-pada-prokariot-dan-eukariot',
                'category' => $categories['genetika-molekuler'],
                'excerpt' => 'Memahami mekanisme kontrol transkripsi melalui operon pada bakteri dan faktor transkripsi pada eukariot.',
                'content' => $this->articleContentGeneRegulation(),
                'tags' => [$tags['ekspresi-gen'], $tags['dna']],
                'glossaries' => [$glossaries['Operon']->id, $glossaries['Escherichia coli']->id],
                'image' => 'regulasi-ekspresi-gen-pada-prokariot-dan-eukariot.webp',
                'days_ago' => 8,
                'view_count' => 1534,
            ],
            [
                'title' => 'Bioinformatika dan Analisis Sekuens Genom',
                'slug' => 'bioinformatika-dan-analisis-sekuens-genom',
                'category' => $categories['bioteknologi'],
                'excerpt' => 'Mengenal pendekatan komputasional untuk menganalisis data sekuens DNA dan memetakan genom organisme.',
                'content' => $this->articleContentBioinformatics(),
                'tags' => [$tags['bioinformatika'], $tags['dna']],
                'glossaries' => [$glossaries['Homo sapiens']->id, $glossaries['CRISPR-Cas9']->id],
                'image' => 'bioinformatika-dan-analisis-sekuens-genom.webp',
                'days_ago' => 9,
                'view_count' => 978,
            ],
            [
                'title' => 'Teknik PCR dan Aplikasinya dalam Diagnosis Molekuler',
                'slug' => 'teknik-pcr-dan-aplikasinya-dalam-diagnosis-molekuler',
                'category' => $categories['bioteknologi'],
                'excerpt' => 'Mempelajari prinsip amplifikasi DNA dengan PCR serta penerapannya dalam deteksi penyakit dan forensik.',
                'content' => $this->articleContentPcr(),
                'tags' => [$tags['pcr'], $tags['enzim']],
                'glossaries' => [$glossaries['PCR']->id, $glossaries['Escherichia coli']->id],
                'image' => 'teknik-pcr-dan-aplikasinya-dalam-diagnosis-molekuler.webp',
                'days_ago' => 11,
                'view_count' => 1645,
            ],
        ];

        return collect($articles)
            ->map(function (array $articleData) use ($admin) {
                $category = $articleData['category'];

                $article = Article::firstOrCreate(
                    ['slug' => $articleData['slug']],
                    [
                        'user_id' => $admin->id,
                        'category_id' => $category->id,
                        'level' => $category->level,
                        'title' => $articleData['title'],
                        'excerpt' => $articleData['excerpt'],
                        'content' => $articleData['content'],
                        'status' => 'published',
                        'published_at' => now()->subDays($articleData['days_ago']),
                        'view_count' => $articleData['view_count'],
                    ],
                );

                $asset = $this->seedArticleCover($admin, $articleData['image']);

                if ($this->shouldUseSeededArticleCover($article->getRawOriginal('featured_image'))) {
                    $article->update(['featured_image' => $asset->url]);
                }

                $article->tags()->syncWithoutDetaching(collect($articleData['tags'])->pluck('id')->all());
                $article->glossaries()->syncWithoutDetaching($articleData['glossaries']);

                return $article;
            })
            ->keyBy('slug');
    }

    private function seedArticleCover(User $owner, string $filename): MediaAsset
    {
        $sourcePath = database_path('seeders/assets/articles/'.$filename);

        if (! is_file($sourcePath)) {
            throw new RuntimeException("Gambar artikel seed tidak ditemukan: {$filename}");
        }

        $imageInfo = getimagesize($sourcePath);

        if ($imageInfo === false
            || $imageInfo[0] !== 1200
            || $imageInfo[1] !== 630
            || $imageInfo[2] !== IMAGETYPE_WEBP) {
            throw new RuntimeException("Gambar artikel seed harus berupa WebP 1200x630: {$filename}");
        }

        $contents = file_get_contents($sourcePath);

        if (! is_string($contents)) {
            throw new RuntimeException("Gambar artikel seed tidak dapat dibaca: {$filename}");
        }

        $storagePath = 'articles/seed/'.$filename;

        if (! Storage::disk('public')->put($storagePath, $contents)) {
            throw new RuntimeException("Gambar artikel seed tidak dapat disimpan: {$filename}");
        }

        return MediaAsset::query()->updateOrCreate(
            ['path' => $storagePath],
            [
                'user_id' => $owner->id,
                'original_name' => $filename,
                'disk' => 'public',
                'mime_type' => 'image/webp',
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'size' => strlen($contents),
            ],
        );
    }

    private function shouldUseSeededArticleCover(?string $featuredImage): bool
    {
        if (blank($featuredImage)) {
            return true;
        }

        $publicArticlePrefix = rtrim(Storage::disk('public')->url('articles'), '/').'/';

        return Str::startsWith($featuredImage, $publicArticlePrefix)
            || Str::startsWith($featuredImage, '/storage/articles/');
    }

    /**
     * @param  Collection<string, Article>  $articles
     */
    private function seedCitations(Collection $articles): void
    {
        $citationMap = [
            'modifikasi-genom-crispr-cas9-dan-implikasinya-dalam-terapi-gen' => [
                ['citation_text' => 'Jinek, M., Chylinski, K., Fonfara, I., Hauer, M., Doudna, J. A., & Charpentier, E. (2012). A programmable dual-RNA-guided DNA endonuclease in adaptive bacterial immunity. Science, 337(6096), 816-821.', 'doi' => '10.1126/science.1225829'],
                ['citation_text' => 'Cong, L., Ran, F. A., Cox, D., et al. (2013). Multiplex genome engineering using CRISPR/Cas systems. Science, 339(6121), 819-823.', 'doi' => '10.1126/science.1231143'],
            ],
            'kinetika-enzim-dan-regulasi-aktivitas-katalitik' => [
                ['citation_text' => 'Michaelis, L., & Menten, M. L. (1913). Die Kinetik der Invertinwirkung. Biochemische Zeitschrift, 49, 333-369.'],
                ['citation_text' => 'Alberts, B., Johnson, A., Lewis, J., Raff, M., Roberts, K., & Walter, P. (2015). Molecular Biology of the Cell (6th ed.). Garland Science.'],
            ],
            'mitokondria-dan-sintesis-atp-dalam-respirasi-seluler-aerobik' => [
                ['citation_text' => 'Mitchell, P. (1961). Coupling of phosphorylation to electron and hydrogen transfer by a chemi-osmotic type of mechanism. Nature, 191, 144-148.', 'doi' => '10.1038/191144a0'],
                ['citation_text' => 'Boyer, P. D. (1997). The ATP synthase — a splendid molecular machine. Annual Review of Biochemistry, 66, 717-749.', 'doi' => '10.1146/annurev.biochem.66.1.717'],
            ],
            'regulasi-ekspresi-gen-pada-prokariot-dan-eukariot' => [
                ['citation_text' => 'Jacob, F., & Monod, J. (1961). Genetic regulatory mechanisms in the synthesis of proteins. Journal of Molecular Biology, 3(3), 318-356.', 'doi' => '10.1016/S0022-2836(61)80072-7'],
                ['citation_text' => 'Ptashne, M., & Gann, A. (2002). Genes & Signals. Cold Spring Harbor Laboratory Press.'],
            ],
            'bioinformatika-dan-analisis-sekuens-genom' => [
                ['citation_text' => 'Lander, E. S., et al. (2001). Initial sequencing and analysis of the human genome. Nature, 409(6822), 860-921.', 'doi' => '10.1038/35057062'],
                ['citation_text' => 'Altschul, S. F., Gish, W., Miller, W., Myers, E. W., & Lipman, D. J. (1990). Basic local alignment search tool. Journal of Molecular Biology, 215(3), 403-410.', 'doi' => '10.1016/S0022-2836(05)80360-2'],
            ],
            'teknik-pcr-dan-aplikasinya-dalam-diagnosis-molekuler' => [
                ['citation_text' => 'Mullis, K. B., & Faloona, F. A. (1987). Specific synthesis of DNA in vitro via a polymerase-catalyzed chain reaction. Methods in Enzymology, 155, 335-350.', 'doi' => '10.1016/0076-6879(87)55023-6'],
                ['citation_text' => 'Saiki, R. K., et al. (1988). Primer-directed enzymatic amplification of DNA with a thermostable DNA polymerase. Science, 239(4839), 487-491.', 'doi' => '10.1126/science.2448875'],
            ],
        ];

        foreach ($citationMap as $slug => $citations) {
            $article = $articles->get($slug);
            if (! $article) {
                continue;
            }

            foreach ($citations as $index => $citation) {
                Citation::firstOrCreate(
                    ['article_id' => $article->id, 'sort_order' => $index + 1],
                    [
                        'citation_text' => $citation['citation_text'],
                        'doi' => $citation['doi'] ?? null,
                        'url' => null,
                    ],
                );
            }
        }
    }

    /**
     * @param  Collection<string, Article>  $articles
     */
    private function seedQuizQuestions(Collection $articles): void
    {
        $quizzes = [
            // ── Existing quiz questions ──────────────────────────────────
            'mekanisme-transport-aktif-pompa-natrium-kalium-pada-membran-sel' => [
                [
                    'question' => 'Berapakah rasio ion Na+ yang dikeluarkan dan ion K+ yang dimasukkan oleh pompa Na+/K+-ATPase untuk setiap ATP yang dihidrolisis?',
                    'options' => [
                        '2 ion Na+ keluar dan 3 ion K+ masuk',
                        '3 ion Na+ keluar dan 2 ion K+ masuk',
                        '3 ion Na+ keluar dan 3 ion K+ masuk',
                        '2 ion Na+ keluar dan 2 ion K+ masuk',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Pompa Na+/K+-ATPase memompa 3 ion natrium keluar sel dan 2 ion kalium masuk sel untuk setiap molekul ATP yang dihidrolisis.',
                ],
                [
                    'question' => 'Termasuk jenis transpor apakah pompa Na+/K+-ATPase?',
                    'options' => [
                        'Transpor aktif sekunder',
                        'Difusi terfasilitasi',
                        'Transpor aktif primer',
                        'Osmosis',
                    ],
                    'correct_option_index' => 2,
                    'explanation' => 'Pompa Na+/K+-ATPase menggunakan energi langsung dari hidrolisis ATP sehingga termasuk transpor aktif primer.',
                ],
            ],
            'modifikasi-genom-crispr-cas9-dan-implikasinya-dalam-terapi-gen' => [
                [
                    'question' => 'Komponen apa yang membawa Cas9 ke lokasi target DNA?',
                    'options' => [
                        'RNA pemandu (gRNA)',
                        'Protein histon',
                        'ATP sintase',
                        'DNA polimerase',
                    ],
                    'correct_option_index' => 0,
                    'explanation' => 'gRNA mengenali urutan target dan membawa Cas9 ke lokasi pemotongan DNA.',
                ],
                [
                    'question' => 'Jenis kerusakan DNA apa yang dihasilkan oleh Cas9 pada lokasi target?',
                    'options' => [
                        'Pemotongan untai ganda',
                        'Sambungan silang protein-DNA',
                        'Metilasi sitosin',
                        'Mutasi titik acak',
                    ],
                    'correct_option_index' => 0,
                    'explanation' => 'Cas9 menghasilkan pemotongan untai ganda yang diperbaiki oleh NHEJ atau HDR.',
                ],
            ],
            'interaksi-simbiosis-mutualisme-dan-parasitisme-dalam-ekosistem-terumbu-karang' => [
                [
                    'question' => 'Apa peran utama zooxanthellae dalam simbiosis dengan koral?',
                    'options' => [
                        'Menyerap nutrisi dari polip',
                        'Menghasilkan oksigen dan glukosa melalui fotosintesis',
                        'Memproduksi rangka kapur koral',
                        'Menghantarkan sinyal listrik',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Zooxanthellae berfotosintesis dan memberikan oksigen serta produk fotosintat kepada inangnya.',
                ],
            ],
            'kinetika-enzim-dan-regulasi-aktivitas-katalitik' => [
                [
                    'question' => 'Apa yang dimaksud dengan konstanta Michaelis (Km) pada kinetika enzim?',
                    'options' => [
                        'Konsentrasi substrat saat laju reaksi mencapai Vmax',
                        'Konsentrasi substrat saat laju reaksi = ½ Vmax',
                        'Laju reaksi maksimum enzim',
                        'Jumlah enzim total dalam sel',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Km adalah konsentrasi substrat ketika laju reaksi enzimatik mencapai setengah Vmax.',
                ],
                [
                    'question' => 'Inhibitor kompetitif bekerja dengan cara...',
                    'options' => [
                        'Mengubah sisi aktif enzim secara ireversibel',
                        'Menduduki sisi aktif sehingga substrat tidak dapat terikat',
                        'Menurunkan jumlah produk akhir',
                        'Menghambat produksi mRNA enzim',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Inhibitor kompetitif bersaing dengan substrat untuk berikatan di sisi aktif enzim.',
                ],
            ],
            'tahapan-mitosis-dan-kontrol-siklus-sel-pada-makhluk-eukariotik' => [
                [
                    'question' => 'Pada tahap mana kromosom berjejer di bidang ekuator sel?',
                    'options' => ['Profase', 'Metafase', 'Anafase', 'Telofase'],
                    'correct_option_index' => 1,
                    'explanation' => 'Metafase ditandai oleh penjejeran kromosom di bidang ekuator melalui spindel mitosis.',
                ],
                [
                    'question' => 'Checkpoint G1 berfungsi untuk...',
                    'options' => [
                        'Memastikan DNA telah direplikasi sempurna',
                        'Memutus spindel jika kromosom tidak tepat',
                        'Memutuskan apakah sel akan membelah atau masuk fase G0',
                        'Memicu apoptosis secara otomatis',
                    ],
                    'correct_option_index' => 2,
                    'explanation' => 'Checkpoint G1 (restriction point) menentukan apakah sel melanjutkan siklus atau berhenti di G0.',
                ],
            ],
            'mitokondria-dan-sintesis-atp-dalam-respirasi-seluler-aerobik' => [
                [
                    'question' => 'Tempat berlangsungnya fosforilasi oksidatif adalah...',
                    'options' => [
                        'Sitoplasma',
                        'Membran luar mitokondria',
                        'Matriks mitokondria',
                        'Membran dalam mitokondria',
                    ],
                    'correct_option_index' => 3,
                    'explanation' => 'Rantai transpor elektron yang membangun gradien proton terdapat pada membran dalam mitokondria.',
                ],
                [
                    'question' => 'ATP sintase bekerja dengan memanfaatkan...',
                    'options' => [
                        'Energi cahaya',
                        'Gradien proton (H+) antar membran',
                        'Reaksi dehidrogenasi langsung',
                        'Glikolisis substrat tingkat',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'ATP sintase menggunakan aliran proton dari membran luar ke matriks untuk memfosforilasi ADP menjadi ATP.',
                ],
            ],

            // ── New quiz questions for 6 new articles ────────────────────
            'fotosintesis-reaksi-terang-dan-siklus-calvin-pada-tumbuhan' => [
                [
                    'question' => 'Di mana reaksi terang fotosintesis berlangsung?',
                    'options' => [
                        'Stroma kloroplas',
                        'Membran tilakoid',
                        'Matriks mitokondria',
                        'Sitoplasma',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Reaksi terang terjadi pada membran tilakoid di mana pigmen klorofil menyerap cahaya dan menghasilkan ATP serta NADPH.',
                ],
                [
                    'question' => 'Apa produk akhir dari siklus Calvin?',
                    'options' => [
                        'Oksigen (O₂)',
                        'Gliseraldehida-3-fosfat (G3P)',
                        'Asam piruvat',
                        'NADH',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Siklus Calvin menghasilkan G3P (gliseraldehida-3-fosfat) yang kemudian digunakan untuk membentuk glukosa dan senyawa organik lainnya.',
                ],
            ],
            'struktur-dan-fungsi-dna-serta-proses-replikasi-semikonservatif' => [
                [
                    'question' => 'Enzim apa yang membuka pilinan untai ganda DNA selama replikasi?',
                    'options' => [
                        'DNA polimerase',
                        'Helikase',
                        'Ligase',
                        'Topoisomerase',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Helikase membuka ikatan hidrogen antar basa nitrogen sehingga kedua untai DNA terpisah pada garpu replikasi.',
                ],
                [
                    'question' => 'Replikasi DNA bersifat semikonservatif, artinya...',
                    'options' => [
                        'Kedua untai baru seluruhnya terdiri dari nukleotida baru',
                        'Setiap molekul DNA anak mengandung satu untai lama dan satu untai baru',
                        'Hanya satu untai yang direplikasi',
                        'DNA disalin secara konservatif di nukleus',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Eksperimen Meselson-Stahl membuktikan bahwa setiap molekul DNA anak terdiri dari satu untai cetakan (lama) dan satu untai yang baru disintesis.',
                ],
            ],
            'evolusi-dan-seleksi-alam-menurut-teori-darwin' => [
                [
                    'question' => 'Mekanisme utama yang mendorong evolusi menurut Darwin adalah...',
                    'options' => [
                        'Mutasi buatan',
                        'Pewarisan sifat didapat (Lamarck)',
                        'Seleksi alam',
                        'Rekayasa genetika',
                    ],
                    'correct_option_index' => 2,
                    'explanation' => 'Darwin mengusulkan bahwa seleksi alam—di mana individu dengan variasi yang lebih adaptif bertahan hidup dan bereproduksi—merupakan penggerak utama evolusi.',
                ],
                [
                    'question' => 'Apa yang dimaksud dengan fitness dalam konteks evolusi?',
                    'options' => [
                        'Kekuatan fisik organisme',
                        'Kemampuan organisme untuk bertahan hidup dan meninggalkan keturunan',
                        'Ukuran tubuh organisme',
                        'Kecepatan lari organisme',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Dalam biologi evolusi, fitness mengacu pada keberhasilan reproduksi suatu organisme yang mewariskan genotipenya ke generasi berikutnya.',
                ],
            ],
            'regulasi-ekspresi-gen-pada-prokariot-dan-eukariot' => [
                [
                    'question' => 'Pada model operon lac, kapan gen struktural akan diekspresikan?',
                    'options' => [
                        'Saat glukosa tersedia melimpah',
                        'Saat laktosa hadir dan menginaktivasi represor',
                        'Saat RNA polimerase tidak aktif',
                        'Saat suhu lingkungan menurun',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Laktosa (melalui allolaktosa) berikatan dengan represor lac, mengubah konformasinya sehingga represor terlepas dari operator dan transkripsi dapat berlangsung.',
                ],
                [
                    'question' => 'Apa perbedaan utama regulasi gen pada eukariot dibanding prokariot?',
                    'options' => [
                        'Eukariot tidak memiliki promotor',
                        'Eukariot menggunakan regulasi multi-level termasuk modifikasi epigenetik',
                        'Prokariot tidak dapat melakukan transkripsi',
                        'Gen eukariot tidak memiliki urutan pengatur',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Regulasi gen pada eukariot terjadi di berbagai level: remodeling kromatin, kontrol transkripsi, pemrosesan RNA, dan regulasi translasi, termasuk modifikasi epigenetik seperti metilasi DNA dan asetilasi histon.',
                ],
            ],
            'bioinformatika-dan-analisis-sekuens-genom' => [
                [
                    'question' => 'Algoritma BLAST digunakan untuk...',
                    'options' => [
                        'Menentukan struktur 3D protein',
                        'Mencari kesamaan sekuens dalam basis data genom',
                        'Mensintesis DNA secara in vitro',
                        'Menghitung konsentrasi enzim',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'BLAST (Basic Local Alignment Search Tool) membandingkan sekuens query dengan database untuk menemukan homologi berdasarkan pencocokan lokal.',
                ],
                [
                    'question' => 'Human Genome Project berhasil memetakan sekitar berapa pasang basa DNA manusia?',
                    'options' => [
                        '1 juta pasang basa',
                        '3 miliar pasang basa',
                        '100 ribu pasang basa',
                        '10 miliar pasang basa',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Human Genome Project menyelesaikan pemetaan sekitar 3 miliar pasang basa yang menyusun genom manusia pada tahun 2003.',
                ],
            ],
            'teknik-pcr-dan-aplikasinya-dalam-diagnosis-molekuler' => [
                [
                    'question' => 'Tahapan denaturasi pada PCR bertujuan untuk...',
                    'options' => [
                        'Menempel primer ke cetakan DNA',
                        'Memisahkan kedua untai DNA dengan pemanasan',
                        'Memperpanjang rantai DNA oleh polimerase',
                        'Mendinginkan campuran reaksi',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Denaturasi (biasanya pada 94–98 °C) memutus ikatan hidrogen antara kedua untai DNA sehingga menjadi untai tunggal yang siap menjadi cetakan.',
                ],
                [
                    'question' => 'Mengapa Taq polymerase digunakan dalam PCR?',
                    'options' => [
                        'Karena bekerja pada suhu rendah',
                        'Karena tahan terhadap suhu tinggi siklus denaturasi',
                        'Karena menghasilkan RNA, bukan DNA',
                        'Karena tidak memerlukan primer',
                    ],
                    'correct_option_index' => 1,
                    'explanation' => 'Taq polymerase diisolasi dari bakteri termofilik Thermus aquaticus dan tetap aktif pada suhu tinggi (72 °C untuk ekstensi, bertahan saat denaturasi 95 °C).',
                ],
            ],
        ];

        foreach ($quizzes as $slug => $questions) {
            $article = $articles->get($slug);
            if (! $article) {
                continue;
            }

            foreach ($questions as $index => $question) {
                QuizQuestion::firstOrCreate(
                    ['article_id' => $article->id, 'sort_order' => $index + 1],
                    $question,
                );
            }
        }
    }

    /**
     * @param  Collection<string, Article>  $articles
     */
    private function seedContentReviews(User $admin, Collection $articles): void
    {
        // Light review history so the article moderation dashboard has demo
        // content. Keys are slugs; values are arrays of [action, notes, daysAgo].
        $reviews = [
            'mekanisme-transport-aktif-pompa-natrium-kalium-pada-membran-sel' => [
                ['action' => 'submitted', 'notes' => 'Draft awal dikirim untuk review.', 'days_ago' => 12],
                ['action' => 'approved', 'notes' => 'Konten lolos review, materi sesuai silabus SMA.', 'days_ago' => 11],
            ],
            'modifikasi-genom-crispr-cas9-dan-implikasinya-dalam-terapi-gen' => [
                ['action' => 'submitted', 'notes' => 'Menggunakan paper sumber primer sebagai referensi utama.', 'days_ago' => 8],
                ['action' => 'approved', 'notes' => 'Sitasi DOI lengkap, kuis sudah diverifikasi.', 'days_ago' => 7],
            ],
            'tahapan-mitosis-dan-kontrol-siklus-sel-pada-makhluk-eukariotik' => [
                ['action' => 'submitted', 'notes' => 'Artikel pengantar siklus sel untuk siswa SMA.', 'days_ago' => 13],
                ['action' => 'changes_requested', 'notes' => 'Tambahkan diagram fase mitosis.', 'days_ago' => 12],
                ['action' => 'approved', 'notes' => 'Revisi sudah lengkap dan jelas.', 'days_ago' => 10],
            ],
            'fotosintesis-reaksi-terang-dan-siklus-calvin-pada-tumbuhan' => [
                ['action' => 'submitted', 'notes' => 'Materi fotosintesis lengkap dengan reaksi terang dan siklus Calvin.', 'days_ago' => 5],
                ['action' => 'approved', 'notes' => 'Penjelasan tahapan jelas, cocok untuk siswa SMA.', 'days_ago' => 4],
            ],
            'regulasi-ekspresi-gen-pada-prokariot-dan-eukariot' => [
                ['action' => 'submitted', 'notes' => 'Artikel tingkat PT tentang regulasi gen.', 'days_ago' => 11],
                ['action' => 'changes_requested', 'notes' => 'Perlu penjelasan lebih detail tentang enhancer dan silencer.', 'days_ago' => 10],
                ['action' => 'approved', 'notes' => 'Revisi memuaskan, penjelasan epigenetik ditambahkan.', 'days_ago' => 8],
            ],
            'teknik-pcr-dan-aplikasinya-dalam-diagnosis-molekuler' => [
                ['action' => 'submitted', 'notes' => 'Artikel PCR dilengkapi referensi paper asli Mullis.', 'days_ago' => 14],
                ['action' => 'approved', 'notes' => 'Materi PCR akurat, aplikasi diagnostik relevan.', 'days_ago' => 12],
            ],
        ];

        foreach ($reviews as $slug => $entries) {
            $article = $articles->get($slug);
            if (! $article) {
                continue;
            }

            foreach ($entries as $entry) {
                ContentReview::firstOrCreate(
                    [
                        'reviewable_id' => $article->id,
                        'reviewable_type' => Article::class,
                        'action' => $entry['action'],
                        'reviewer_id' => $admin->id,
                    ],
                    [
                        'notes' => $entry['notes'],
                        'created_at' => now()->subDays($entry['days_ago']),
                        'updated_at' => now()->subDays($entry['days_ago']),
                    ],
                );
            }
        }
    }

    /**
     * Populate the article_views table with realistic analytics data spread
     * across the last 30 days so the analytics dashboard renders meaningful
     * charts out of the box.
     *
     * @param  Collection<string, Article>  $articles
     */
    private function seedArticleViews(Collection $articles): void
    {
        $articles->each(function (Article $article) {
            // Scale view records proportionally to view_count so popular
            // articles have more distinct daily entries.
            $viewsToGenerate = (int) min(40, max(8, $article->view_count / 80));

            for ($i = 0; $i < $viewsToGenerate; $i++) {
                $daysAgo = $i % 30; // spread evenly across last 30 days
                $hash = hash('sha256', 'seed_visitor_'.$article->id.'_'.$i);

                ArticleView::firstOrCreate([
                    'article_id' => $article->id,
                    'visitor_hash' => $hash,
                    'viewed_on' => now()->subDays($daysAgo)->toDateString(),
                ]);
            }
        });
    }

    /**
     * @param  Collection<string, Article>  $articles
     */
    private function seedCourses(User $admin, Collection $articles): void
    {
        $courses = [
            // ── Existing 2 courses (expanded) ────────────────────────────
            [
                'title' => 'Dasar Sel dan Ekosistem SMA',
                'slug' => 'dasar-sel-dan-ekosistem-sma',
                'description' => 'Kursus pengantar untuk memahami membran sel, transport aktif, dan relasi organisme dalam ekosistem.',
                'level' => 'sma',
                'sort_order' => 10,
                'palette' => ['primary' => [37, 99, 235], 'secondary' => [14, 165, 233], 'accent' => [255, 255, 255], 'motif' => 'cells'],
                'modules' => [
                    [
                        'title' => 'Fisiologi Sel',
                        'description' => 'Memahami struktur membran dan transport ion.',
                        'lessons' => [
                            ['article' => 'mekanisme-transport-aktif-pompa-natrium-kalium-pada-membran-sel', 'title' => 'Transport Aktif Membran Sel'],
                            ['article' => 'tahapan-mitosis-dan-kontrol-siklus-sel-pada-makhluk-eukariotik', 'title' => 'Siklus Sel dan Mitosis'],
                        ],
                    ],
                    [
                        'title' => 'Interaksi Ekosistem',
                        'description' => 'Membaca pola hubungan organisme dalam ekosistem laut.',
                        'lessons' => [
                            ['article' => 'interaksi-simbiosis-mutualisme-dan-parasitisme-dalam-ekosistem-terumbu-karang', 'title' => 'Simbiosis Terumbu Karang'],
                        ],
                    ],
                    [
                        'title' => 'Pengantar Fotosintesis',
                        'description' => 'Memahami proses pengubahan energi cahaya menjadi energi kimia.',
                        'lessons' => [
                            ['article' => 'fotosintesis-reaksi-terang-dan-siklus-calvin-pada-tumbuhan', 'title' => 'Reaksi Terang dan Siklus Calvin'],
                        ],
                    ],
                ],
                'questions' => [
                    [
                        'question' => 'Pompa Na+/K+-ATPase menggunakan energi dari molekul apa?',
                        'options' => ['ATP', 'DNA', 'Glukosa bebas', 'Oksigen'],
                        'correct_option_index' => 0,
                        'explanation' => 'Pompa Na+/K+-ATPase memakai hidrolisis ATP untuk memindahkan ion melawan gradien konsentrasi.',
                    ],
                    [
                        'question' => 'Hubungan koral dan zooxanthellae pada terumbu karang terutama bersifat?',
                        'options' => ['Mutualisme', 'Predasi', 'Komensalisme', 'Amensalisme'],
                        'correct_option_index' => 0,
                        'explanation' => 'Koral menyediakan tempat hidup, sementara zooxanthellae membantu menyediakan hasil fotosintesis.',
                    ],
                    [
                        'question' => 'Pada tahap mana kromosom berjejer di bidang ekuator sel?',
                        'options' => ['Profase', 'Metafase', 'Anafase', 'Telofase'],
                        'correct_option_index' => 1,
                        'explanation' => 'Metafase ditandai oleh penjejeran kromosom di bidang ekuator melalui spindel mitosis.',
                    ],
                    [
                        'question' => 'Di mana reaksi terang fotosintesis berlangsung?',
                        'options' => ['Stroma kloroplas', 'Membran tilakoid', 'Sitoplasma', 'Matriks mitokondria'],
                        'correct_option_index' => 1,
                        'explanation' => 'Reaksi terang terjadi pada membran tilakoid di mana pigmen klorofil menyerap cahaya.',
                    ],
                ],
            ],
            [
                'title' => 'Genetika dan Biokimia Molekuler PT',
                'slug' => 'genetika-dan-biokimia-molekuler-pt',
                'description' => 'Kursus perguruan tinggi tentang CRISPR-Cas9, ekspresi genom, dan regulasi aktivitas enzim.',
                'level' => 'pt',
                'sort_order' => 20,
                'palette' => ['primary' => [124, 58, 237], 'secondary' => [236, 72, 153], 'accent' => [255, 255, 255], 'motif' => 'dna'],
                'modules' => [
                    [
                        'title' => 'Teknologi Genom',
                        'description' => 'Menganalisis prinsip penyuntingan genom modern.',
                        'lessons' => [
                            ['article' => 'modifikasi-genom-crispr-cas9-dan-implikasinya-dalam-terapi-gen', 'title' => 'CRISPR-Cas9 untuk Terapi Gen'],
                        ],
                    ],
                    [
                        'title' => 'Biokimia Enzim',
                        'description' => 'Memahami kinetika dan regulasi katalisis.',
                        'lessons' => [
                            ['article' => 'kinetika-enzim-dan-regulasi-aktivitas-katalitik', 'title' => 'Kinetika dan Regulasi Enzim'],
                            ['article' => 'mitokondria-dan-sintesis-atp-dalam-respirasi-seluler-aerobik', 'title' => 'Fosforilasi Oksidatif Mitokondria'],
                        ],
                    ],
                    [
                        'title' => 'Regulasi Gen Lanjut',
                        'description' => 'Mempelajari kontrol ekspresi gen pada tingkat molekuler.',
                        'lessons' => [
                            ['article' => 'regulasi-ekspresi-gen-pada-prokariot-dan-eukariot', 'title' => 'Regulasi Ekspresi Gen'],
                        ],
                    ],
                ],
                'questions' => [
                    [
                        'question' => 'Protein Cas9 diarahkan ke target DNA oleh komponen apa?',
                        'options' => ['RNA pemandu', 'Lipid bilayer', 'ATP sintase', 'Histon H1'],
                        'correct_option_index' => 0,
                        'explanation' => 'RNA pemandu mengenali urutan target dan membawa Cas9 ke lokasi pemotongan DNA.',
                    ],
                    [
                        'question' => 'Model Michaelis-Menten menjelaskan relasi antara laju reaksi enzim dan apa?',
                        'options' => ['Konsentrasi substrat', 'Tekanan osmotik', 'Jumlah kromosom', 'Ukuran sel'],
                        'correct_option_index' => 0,
                        'explanation' => 'Kinetika Michaelis-Menten memodelkan perubahan laju reaksi terhadap konsentrasi substrat.',
                    ],
                    [
                        'question' => 'Tempat berlangsungnya fosforilasi oksidatif adalah...',
                        'options' => ['Sitoplasma', 'Membran luar mitokondria', 'Matriks mitokondria', 'Membran dalam mitokondria'],
                        'correct_option_index' => 3,
                        'explanation' => 'Rantai transpor elektron yang membangun gradien proton terdapat pada membran dalam mitokondria.',
                    ],
                    [
                        'question' => 'Pada model operon lac, kapan gen struktural akan diekspresikan?',
                        'options' => ['Saat glukosa melimpah', 'Saat laktosa hadir dan menginaktivasi represor', 'Saat suhu turun', 'Saat RNA polimerase dihambat'],
                        'correct_option_index' => 1,
                        'explanation' => 'Laktosa (melalui allolaktosa) berikatan dengan represor lac sehingga transkripsi dapat berlangsung.',
                    ],
                ],
            ],

            // ── 2 new courses ────────────────────────────────────────────
            [
                'title' => 'Fotosintesis dan Evolusi Kehidupan SMA',
                'slug' => 'fotosintesis-dan-evolusi-kehidupan-sma',
                'description' => 'Kursus SMA tentang proses fotosintesis, struktur DNA, dan teori evolusi yang membentuk keanekaragaman hayati.',
                'level' => 'sma',
                'sort_order' => 30,
                'palette' => ['primary' => [22, 163, 74], 'secondary' => [34, 197, 94], 'accent' => [255, 255, 255], 'motif' => 'photosynthesis'],
                'modules' => [
                    [
                        'title' => 'Energi Cahaya dan Metabolisme',
                        'description' => 'Memahami fotosintesis dan peran DNA dalam pewarisan sifat.',
                        'lessons' => [
                            ['article' => 'fotosintesis-reaksi-terang-dan-siklus-calvin-pada-tumbuhan', 'title' => 'Fotosintesis: Reaksi Terang & Calvin'],
                            ['article' => 'struktur-dan-fungsi-dna-serta-proses-replikasi-semikonservatif', 'title' => 'DNA dan Replikasi'],
                        ],
                    ],
                    [
                        'title' => 'Evolusi dan Keanekaragaman Hayati',
                        'description' => 'Menelusuri mekanisme evolusi yang membentuk keragaman spesies.',
                        'lessons' => [
                            ['article' => 'evolusi-dan-seleksi-alam-menurut-teori-darwin', 'title' => 'Seleksi Alam Darwin'],
                        ],
                    ],
                ],
                'questions' => [
                    [
                        'question' => 'Produk reaksi terang yang digunakan dalam siklus Calvin adalah...',
                        'options' => ['ATP dan NADPH', 'Oksigen dan Glukosa', 'CO₂ dan H₂O', 'DNA dan RNA'],
                        'correct_option_index' => 0,
                        'explanation' => 'Reaksi terang menghasilkan ATP dan NADPH yang kemudian digunakan dalam siklus Calvin untuk fiksasi karbon.',
                    ],
                    [
                        'question' => 'Replikasi DNA bersifat semikonservatif, artinya...',
                        'options' => [
                            'Satu untai lama + satu untai baru',
                            'Kedua untai seluruhnya baru',
                            'Hanya satu untai disalin',
                            'DNA tidak berubah',
                        ],
                        'correct_option_index' => 0,
                        'explanation' => 'Setiap molekul DNA anak mengandung satu untai cetakan (lama) dan satu untai yang baru disintesis.',
                    ],
                    [
                        'question' => 'Darwin mengusulkan bahwa penggerak utama evolusi adalah...',
                        'options' => ['Seleksi alam', 'Mutasi buatan', 'Kloning gen', 'Pewarisan sifat didapat'],
                        'correct_option_index' => 0,
                        'explanation' => 'Seleksi alam memungkinkan individu yang lebih adaptif bertahan hidup dan mewariskan sifatnya.',
                    ],
                ],
            ],
            [
                'title' => 'Bioteknologi Molekuler Terapan PT',
                'slug' => 'bioteknologi-molekuler-terapan-pt',
                'description' => 'Kursus perguruan tinggi tentang bioinformatika, teknik PCR, dan regulasi ekspresi gen dalam konteks bioteknologi modern.',
                'level' => 'pt',
                'sort_order' => 40,
                'palette' => ['primary' => [0, 131, 143], 'secondary' => [0, 188, 212], 'accent' => [255, 255, 255], 'motif' => 'genome'],
                'modules' => [
                    [
                        'title' => 'Genomika dan Bioinformatika',
                        'description' => 'Pendekatan komputasional untuk analisis data genom.',
                        'lessons' => [
                            ['article' => 'bioinformatika-dan-analisis-sekuens-genom', 'title' => 'Analisis Sekuens Genom'],
                            ['article' => 'regulasi-ekspresi-gen-pada-prokariot-dan-eukariot', 'title' => 'Regulasi Ekspresi Gen'],
                        ],
                    ],
                    [
                        'title' => 'Teknik Laboratorium Molekuler',
                        'description' => 'Metode amplifikasi dan deteksi DNA.',
                        'lessons' => [
                            ['article' => 'teknik-pcr-dan-aplikasinya-dalam-diagnosis-molekuler', 'title' => 'PCR dalam Diagnostik'],
                        ],
                    ],
                ],
                'questions' => [
                    [
                        'question' => 'BLAST digunakan untuk...',
                        'options' => ['Mencari homologi sekuens', 'Mengamplifikasi DNA', 'Memotong DNA restriksi', 'Mensintesis protein'],
                        'correct_option_index' => 0,
                        'explanation' => 'BLAST (Basic Local Alignment Search Tool) mencari kesamaan sekuens dalam database genom.',
                    ],
                    [
                        'question' => 'Tahap annealing pada PCR bertujuan untuk...',
                        'options' => [
                            'Menempel primer ke cetakan DNA untai tunggal',
                            'Memisahkan untai ganda DNA',
                            'Membangun rantai DNA baru',
                            'Menghancurkan protein kontaminan',
                        ],
                        'correct_option_index' => 0,
                        'explanation' => 'Pada suhu annealing (50–65 °C), primer oligonukleotida menempel pada sekuens komplementer di cetakan DNA.',
                    ],
                    [
                        'question' => 'Operon pada prokariot terdiri dari komponen berikut, kecuali...',
                        'options' => ['Enhancer', 'Promotor', 'Operator', 'Gen struktural'],
                        'correct_option_index' => 0,
                        'explanation' => 'Enhancer adalah elemen pengatur yang ditemukan pada eukariot, bukan bagian dari model operon prokariot.',
                    ],
                ],
            ],
        ];

        foreach ($courses as $courseData) {
            $course = Course::query()->firstOrCreate(
                ['slug' => $courseData['slug']],
                [
                    'user_id' => $admin->id,
                    'title' => $courseData['title'],
                    'description' => $courseData['description'],
                    'level' => $courseData['level'],
                    'status' => 'published',
                    'passing_score' => 70,
                    'sort_order' => $courseData['sort_order'],
                    'published_at' => now()->subDay(),
                ],
            );

            if (blank($course->featured_image)) {
                $asset = app(CoverImageGenerator::class)->render(
                    owner: $admin,
                    title: $courseData['title'],
                    subtitle: $courseData['description'],
                    palette: $courseData['palette'],
                    badges: [$courseData['level'] === 'sma' ? 'SMA' : 'PT', 'Kursus'],
                    folder: 'courses',
                );
                $course->update(['featured_image' => $asset->url]);
            }

            $modules = collect($courseData['modules'])->map(fn (array $module): array => [
                'title' => $module['title'],
                'description' => $module['description'],
                'lessons' => collect($module['lessons'])->map(fn (array $lesson): array => [
                    'article_id' => $articles->get($lesson['article'])?->id,
                    'title' => $lesson['title'],
                    'is_required' => true,
                ])->all(),
            ])->all();

            app(SyncCourseContent::class)->handle($course, $modules, $courseData['questions'], prune: false);
        }
    }

    /**
     * Enroll sample pembaca users in the published courses, mark a realistic
     * set of lessons as complete, and persist passing + failing final exam
     * attempts so the LMS dashboard + certificate page both have demo data
     * to render.
     */
    private function seedEnrollmentsAndProgress(User $admin): void
    {
        $andini = User::query()->where('email', 'andini@biology.learn')->first();
        $bram = User::query()->where('email', 'bram@biology.learn')->first();
        $citra = User::query()->where('email', 'citra@biology.learn')->first();
        $farhan = User::query()->where('email', 'farhan@biology.learn')->first();
        if (! $andini || ! $bram || ! $citra || ! $farhan) {
            return;
        }

        $smaCourse = Course::query()->where('slug', 'dasar-sel-dan-ekosistem-sma')->first();
        $ptCourse = Course::query()->where('slug', 'genetika-dan-biokimia-molekuler-pt')->first();
        $smaEvoCourse = Course::query()->where('slug', 'fotosintesis-dan-evolusi-kehidupan-sma')->first();
        $ptBiotechCourse = Course::query()->where('slug', 'bioteknologi-molekuler-terapan-pt')->first();
        if (! $smaCourse || ! $ptCourse) {
            return;
        }

        $smaLessons = $smaCourse->lessons()->get();
        $ptLessons = $ptCourse->lessons()->get();

        // ── Andini ───────────────────────────────────────────────────
        // SMA course: completed all lessons, passed final exam, got certificate.
        $andiniEnrollment = $this->enrollAndProgress($andini, $smaCourse, $smaLessons, completed: true, daysAgo: 14);
        // PT course: partial progress (1 lesson).
        $this->enrollAndProgress($andini, $ptCourse, $ptLessons, completed: false, partial: 1, daysAgo: 4);

        // ── Bram ─────────────────────────────────────────────────────
        // PT course: partial progress, failing attempt.
        $bramEnrollment = $this->enrollAndProgress($bram, $ptCourse, $ptLessons, completed: true, daysAgo: 6);
        if ($bramEnrollment->wasRecentlyCreated) {
            $this->recordFinalExamAttempt($bram, $ptCourse, passed: false, daysAgo: 2);
        }

        // ── Citra ────────────────────────────────────────────────────
        // New SMA Fotosintesis & Evolusi course: in progress (1 lesson).
        if ($smaEvoCourse) {
            $smaEvoLessons = $smaEvoCourse->lessons()->get();
            $this->enrollAndProgress($citra, $smaEvoCourse, $smaEvoLessons, completed: false, partial: 1, daysAgo: 3);
        }
        // SMA course: enrolled, partial progress.
        $this->enrollAndProgress($citra, $smaCourse, $smaLessons, completed: false, partial: 2, daysAgo: 7);

        // ── Farhan ───────────────────────────────────────────────────
        // PT Bioteknologi course: completed all, passed exam, got certificate.
        if ($ptBiotechCourse) {
            $ptBiotechLessons = $ptBiotechCourse->lessons()->get();
            $biotechEnrollment = $this->enrollAndProgress($farhan, $ptBiotechCourse, $ptBiotechLessons, completed: true, daysAgo: 10);
            $biotechAttempt = $biotechEnrollment->wasRecentlyCreated
                ? $this->recordFinalExamAttempt($farhan, $ptBiotechCourse, passed: true, daysAgo: 5)
                : null;
            if ($biotechAttempt) {
                Certificate::firstOrCreate(
                    ['course_id' => $ptBiotechCourse->id, 'user_id' => $farhan->id],
                    [
                        'final_exam_attempt_id' => $biotechAttempt->id,
                        'certificate_number' => Certificate::generateNumber(),
                        'issued_at' => $biotechAttempt->attempted_at ?? now(),
                    ],
                );
            }
        }
        // PT course: enrolled, partial progress.
        $this->enrollAndProgress($farhan, $ptCourse, $ptLessons, completed: false, partial: 2, daysAgo: 8);

        // ── Andini's passing attempt for SMA course → certificate ────
        $smaAttempt = $andiniEnrollment->wasRecentlyCreated
            ? $this->recordFinalExamAttempt($andini, $smaCourse, passed: true, daysAgo: 3)
            : null;
        if ($smaAttempt) {
            Certificate::firstOrCreate(
                ['course_id' => $smaCourse->id, 'user_id' => $andini->id],
                [
                    'final_exam_attempt_id' => $smaAttempt->id,
                    'certificate_number' => Certificate::generateNumber(),
                    'issued_at' => $smaAttempt->attempted_at ?? now(),
                ],
            );
        }
    }

    /**
     * @param  Collection<int, CourseLesson>  $lessons
     */
    private function enrollAndProgress(
        User $user,
        Course $course,
        Collection $lessons,
        bool $completed,
        ?int $partial = null,
        int $daysAgo = 7,
    ): CourseEnrollment {
        $enrollment = CourseEnrollment::query()->firstOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            [
                'status' => $completed ? 'completed' : 'active',
                'enrolled_at' => now()->subDays($daysAgo),
                'completed_at' => $completed ? now()->subDays(max(0, $daysAgo - 1)) : null,
            ],
        );

        if (! $enrollment->wasRecentlyCreated) {
            return $enrollment;
        }

        $lessonsToMark = $completed
            ? $lessons
            : ($partial !== null ? $lessons->take($partial) : collect());

        $stampedAt = now()->subDays(max(1, $daysAgo - 1));
        foreach ($lessonsToMark as $lesson) {
            LessonProgress::firstOrCreate(
                ['course_lesson_id' => $lesson->id, 'user_id' => $user->id],
                ['completed_at' => $stampedAt],
            );
        }

        return $enrollment;
    }

    private function recordFinalExamAttempt(User $user, Course $course, bool $passed, int $daysAgo): ?FinalExamAttempt
    {
        $questions = $course->examQuestions()->get();
        if ($questions->isEmpty()) {
            return null;
        }

        $correct = $passed ? $questions->count() : (int) floor($questions->count() / 2);
        $score = max(0, min($questions->count(), $correct));
        $total = $questions->count();
        $percentage = $total > 0 ? (int) round(($score / $total) * 100) : 0;
        $actuallyPassed = $percentage >= $course->passing_score;

        return FinalExamAttempt::query()->firstOrCreate([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'passed' => $actuallyPassed,
        ], [
            'question_snapshot' => $questions->map(fn ($question): array => $question->only(['question', 'options', 'correct_option_index', 'explanation', 'sort_order']))->all(),
            'answers' => $questions->map(fn ($question, $index) => $index < $score ? $question->correct_option_index : ($question->correct_option_index + 1) % max(1, count($question->options)))->values()->all(),
            'score' => $score,
            'total_questions' => $total,
            'percentage' => $percentage,
            'attempted_at' => now()->subDays($daysAgo),
        ]);
    }

    private function seedNewsletterSubscriptions(): void
    {
        $subscribers = [
            ['email' => 'newsletter1@example.com', 'daysAgo' => 20],
            ['email' => 'newsletter2@example.com', 'daysAgo' => 12],
            ['email' => 'newsletter3@example.com', 'daysAgo' => 5],
            ['email' => 'newsletter4@example.com', 'daysAgo' => 3],
            ['email' => 'newsletter5@example.com', 'daysAgo' => 1],
        ];

        foreach ($subscribers as $subscriber) {
            NewsletterSubscription::firstOrCreate(
                ['email' => $subscriber['email']],
                [
                    'status' => 'subscribed',
                    'unsubscribe_token' => Str::random(48),
                    'subscribed_at' => now()->subDays($subscriber['daysAgo']),
                ],
            );
        }
    }

    // -----------------------------------------------------------------
    // Article content blocks. Kept as plain strings so the seeder file
    // remains readable; each function returns an HTML string
    // (the article model stores long-form HTML).
    // -----------------------------------------------------------------

    private function articleContentMembrane(): string
    {
        return <<<'HTML'
            <p>Membran sel eukariotik bertindak sebagai penghalang dinamis yang membatasi lingkungan internal sel dari medium ekstraseluler. Struktur lipid bilayer yang diperkuat oleh protein integral dan periferal memungkinkan sel mempertahankan gradien ion yang esensial bagi kehidupan.</p>
            <p>Pompa <em>Na+/K+-ATPase</em> adalah protein trans-membran yang memanfaatkan energi hidrolisis ATP untuk memindahkan tiga ion natrium keluar sel dan dua ion kalium masuk sel pada setiap siklus. Proses ini mempertahankan potensial membran istirahat, mendukung konduksi saraf, dan mengatur volume sel.</p>
            <p>Gangguan fungsi pompa ini berkaitan dengan berbagai kondisi patologis, termasuk gangguan neurologis dan kardiovaskular, sehingga menjadikannya target farmakologis yang penting dalam dunia kedokteran modern.</p>
        HTML;
    }

    private function articleContentCrispr(): string
    {
        return <<<'HTML'
            <p>Sistem <em>CRISPR-Cas9</em> pada awalnya merupakan mekanisme pertahanan adaptif pada bakteri terhadap infeksi fag. Kompleks Cas9 dipandu oleh RNA pemandu (gRNA) untuk mengenali urutan DNA spesifik dan membuat pemotongan untai ganda di lokasi yang diinginkan.</p>
            <p>Kemampuan menyunting genom secara presisi ini dimanfaatkan dalam terapi gen untuk memperbaiki mutasi penyebab penyakit. Contohnya termasuk terapi untuk distrofi otot, anemia sel sabit, dan beberapa jenis kanker darah yang sudah memasuki uji klinis.</p>
            <p>Tantangan utama yang masih diteliti adalah meningkatkan spesifisitas sistem, mengurangi efek off-target, dan merancang metode pengiriman yang efisien ke jaringan target tanpa memicu respons imun berlebihan.</p>
        HTML;
    }

    private function articleContentCoral(): string
    {
        return <<<'HTML'
            <p>Terumbu karang merupakan salah satu ekosistem paling produktif di lautan, dan keberadaannya sangat bergantung pada hubungan mutualisme antara koral dan alga simbiotik bernama <em>zooxanthellae</em>. Alga menyediakan produk fotosintesis berupa glukosa dan oksigen, sementara koral memberikan nutrisi dan tempat berlindung.</p>
            <p>Peningkatan suhu laut dan polusi dapat memutuskan simbiosis ini sehingga alga keluar dari jaringan koral. Fenomena yang dikenal sebagai <em>coral bleaching</em> ini menurunkan kemampuan koral untuk tumbuh dan memperbanyak diri.</p>
            <p>Di sisi lain, hubungan parasitisme juga dapat terjadi ketika organisme lain seperti cacing atau krustasea menempel pada koral dan menyerap nutrisi tanpa memberikan manfaat, sehingga menambah tekanan pada koloni yang sudah rentan.</p>
        HTML;
    }

    private function articleContentEnzyme(): string
    {
        return <<<'HTML'
            <p>Enzim adalah biokatalis protein yang mempercepat reaksi kimia dengan menurunkan energi aktivasi. Aktivitas katalitiknya dipengaruhi oleh suhu, pH, konsentrasi substrat, dan keberadaan inhibitor atau aktivator.</p>
            <p>Model kinetika <em>Michaelis-Menten</em> menjelaskan hubungan antara laju reaksi awal (V0) dan konsentrasi substrat [S]. Konstanta Michaelis (Km) menunjukkan afinitas enzim terhadap substrat, sedangkan Vmax adalah laju maksimum ketika enzim jenuh substrat.</p>
            <p>Regulasi aktivitas enzim dapat berlangsung melalui inhibisi kompetitif, non-kompetitif, atau alosterik. Modifikasi kovalen seperti fosforilasi dan ubiquitinasi juga merupakan mekanisme umum untuk mengendalikan fungsi enzim dalam jalur metabolisme sel.</p>
        HTML;
    }

    private function articleContentMitosis(): string
    {
        return <<<'HTML'
            <p>Mitosis adalah proses pembelahan inti sel eukariotik yang menghasilkan dua sel anak dengan jumlah kromosom identik. Proses ini terjadi dalam empat fase utama, yaitu profase, metafase, anafase, dan telofase, yang diikuti oleh sitokinesis.</p>
            <p>Pada profase, kromatin memadat menjadi kromosom dan spindel mitosis mulai terbentuk. Saat metafase, kromosom berjejer di bidang ekuator sel dan setiap kromatid sister terhubung ke mikrotubulus spindel dari kutub yang berlawanan.</p>
            <p>Anafase memisahkan kromatid sister menuju kutub berlawanan, dan telofase membentuk kembali membran nukleus di sekitar masing-masing set kromosom. Checkpoint siklus sel seperti G1/S, G2/M, dan spindle assembly checkpoint memastikan setiap fase berjalan akurat sebelum berpindah ke tahap berikutnya.</p>
        HTML;
    }

    private function articleContentMitochondria(): string
    {
        return <<<'HTML'
            <p>Mitokondria adalah organel bermembran ganda yang menjadi pusat respirasi seluler aerobik. Membran dalamnya yang kaya protein merupakan tempat rangkaian kompleks transpor elektron membangun gradien proton yang melintasi membran.</p>
            <p>Aliran elektron dari NADH dan FADH2 melewati kompleks I–IV sambil memompa proton ke ruang intermembran. Proton kemudian kembali ke matriks melalui ATP sintase, dan energi yang dilepaskan digunakan untuk memfosforilasi ADP menjadi ATP.</p>
            <p>Efisiensi fosforilasi oksidatif dipengaruhi oleh ketersediaan oksigen, integritas membran mitokondria, serta regulasi oleh uncoupling protein. Disfungsi mitokondria berkaitan dengan berbagai penyakit neurodegeneratif dan metabolik, sehingga menjadikannya fokus riset biomedis kontemporer.</p>
        HTML;
    }

    // ── New article content methods ──────────────────────────────────

    private function articleContentPhotosynthesis(): string
    {
        return <<<'HTML'
            <p>Fotosintesis merupakan proses fundamental yang mengubah energi cahaya menjadi energi kimia dalam bentuk glukosa. Proses ini berlangsung di dalam <em>kloroplas</em>, organel yang mengandung pigmen <em>klorofil</em> untuk menyerap spektrum cahaya merah dan biru. Fotosintesis terdiri dari dua tahap utama: reaksi terang dan siklus Calvin.</p>
            <p>Reaksi terang terjadi pada <em>membran tilakoid</em> dan menghasilkan ATP serta NADPH melalui fotolisis air dan transpor elektron. Fotosistem II menyerap foton dan memecah molekul air menjadi oksigen, proton, dan elektron. Elektron kemudian mengalir melalui rantai transpor menuju Fotosistem I, membangun gradien proton yang dimanfaatkan ATP sintase.</p>
            <p>Siklus Calvin berlangsung di <em>stroma kloroplas</em> dan menggunakan ATP serta NADPH dari reaksi terang untuk memfiksasi CO₂ menjadi senyawa organik tiga-karbon (G3P). Enzim <em>RuBisCO</em> mengatalisis langkah pertama fiksasi karbon, menjadikannya enzim paling melimpah di biosfer. Tiga putaran siklus Calvin menghasilkan satu molekul G3P neto yang dapat dikonversi menjadi glukosa dan senyawa organik lainnya.</p>
        HTML;
    }

    private function articleContentDnaReplication(): string
    {
        return <<<'HTML'
            <p>DNA (deoxyribonucleic acid) tersusun dari dua untai polinukleotida yang saling berpilin membentuk struktur <em>double helix</em> sebagaimana diusulkan oleh Watson dan Crick pada tahun 1953. Setiap nukleotida terdiri dari gula deoksiribosa, gugus fosfat, dan satu dari empat basa nitrogen: adenin (A), timin (T), guanin (G), dan sitosin (C). Pasangan basa komplementer (A-T dan G-C) dihubungkan oleh ikatan hidrogen.</p>
            <p>Replikasi DNA bersifat <em>semikonservatif</em>, sebagaimana dibuktikan oleh eksperimen Meselson dan Stahl. Proses dimulai pada titik asal replikasi (origin of replication) di mana enzim <em>helikase</em> membuka pilinan untai ganda, membentuk garpu replikasi. Protein pengikat untai tunggal (SSB) menstabilkan untai yang sudah terpisah.</p>
            <p>Enzim <em>DNA polimerase III</em> mensintesis untai baru dengan arah 5'→3', membaca cetakan dengan arah 3'→5'. Pada untai leading, sintesis berlangsung kontinu searah garpu replikasi, sedangkan pada untai lagging, sintesis terjadi dalam fragmen-fragmen Okazaki yang kemudian disambung oleh enzim <em>DNA ligase</em>. Mekanisme proofreading pada DNA polimerase memastikan tingkat kesalahan replikasi sangat rendah, sekitar 1 per 10⁹ basa.</p>
        HTML;
    }

    private function articleContentEvolution(): string
    {
        return <<<'HTML'
            <p>Teori evolusi melalui <em>seleksi alam</em> yang dikemukakan oleh Charles Darwin dalam karyanya <em>On the Origin of Species</em> (1859) merupakan salah satu pilar utama biologi modern. Darwin mengamati bahwa dalam setiap populasi terdapat variasi fenotipe antar individu, dan variasi ini sebagian bersifat herediter. Individu dengan sifat yang lebih menguntungkan dalam lingkungan tertentu memiliki peluang bertahan hidup dan bereproduksi lebih tinggi.</p>
            <p>Bukti evolusi berasal dari berbagai disiplin ilmu: paleontologi menyajikan rekaman fosil transisi, anatomi komparatif menunjukkan struktur homolog antar spesies, biogeografi menjelaskan distribusi flora-fauna di kepulauan, dan biologi molekuler mengungkap kesamaan sekuens DNA antar organisme. Contoh klasik termasuk evolusi paruh burung finch di Kepulauan Galápagos yang beradaptasi terhadap sumber makanan berbeda.</p>
            <p>Selain seleksi alam, mekanisme evolusi lainnya meliputi <em>genetic drift</em> (pergeseran frekuensi alel secara acak terutama pada populasi kecil), aliran gen (migrasi individu antar populasi), dan mutasi yang menghasilkan variasi genetik baru. Spesiasi dapat terjadi secara alopatrik (pemisahan geografis) atau simpatrik (isolasi reproduksi tanpa pemisahan fisik), menghasilkan keanekaragaman hayati yang kita saksikan saat ini.</p>
        HTML;
    }

    private function articleContentGeneRegulation(): string
    {
        return <<<'HTML'
            <p>Regulasi ekspresi gen merupakan mekanisme yang memungkinkan sel mengontrol kapan, di mana, dan seberapa banyak suatu gen diekspresikan menjadi produk fungsional. Pada <em>prokariot</em>, regulasi terutama terjadi di tingkat transkripsi melalui sistem <em>operon</em>. Model operon <em>lac</em> pada <em>Escherichia coli</em> menggambarkan bagaimana kelompok gen struktural dikendalikan oleh satu promotor dan operator yang sama.</p>
            <p>Operon lac bersifat <em>inducible</em>: dalam keadaan tanpa laktosa, protein represor mengikat operator dan menghambat transkripsi. Ketika laktosa hadir, allolaktosa (isomer laktosa) berikatan dengan represor sehingga mengubah konformasinya dan melepaskannya dari operator, memungkinkan RNA polimerase mentranskripsi gen-gen yang mengkode enzim pemecah laktosa. Regulasi positif oleh CAP-cAMP menambah lapisan kontrol tergantung ketersediaan glukosa.</p>
            <p>Pada <em>eukariot</em>, regulasi gen jauh lebih kompleks dan berlangsung di berbagai level. Modifikasi epigenetik seperti <em>metilasi DNA</em> dan <em>asetilasi histon</em> mengubah aksesibilitas kromatin tanpa mengubah sekuens DNA. Faktor transkripsi spesifik berikatan dengan <em>enhancer</em> dan <em>silencer</em> yang dapat terletak ribuan pasang basa dari promotor. Pemrosesan pasca-transkripsi termasuk alternative splicing, dan regulasi pasca-translasi melalui fosforilasi dan ubiquitinasi, memberikan kontrol presisi terhadap ekspresi gen dalam jaringan dan tahap perkembangan yang berbeda.</p>
        HTML;
    }

    private function articleContentBioinformatics(): string
    {
        return <<<'HTML'
            <p><em>Bioinformatika</em> adalah disiplin interdisipliner yang mengintegrasikan biologi, ilmu komputer, dan statistik untuk menganalisis data biologis berskala besar, khususnya sekuens DNA, RNA, dan protein. Perkembangan teknologi sekuensing generasi selanjutnya (NGS) telah menghasilkan ledakan data genomik yang memerlukan alat komputasional canggih untuk interpretasinya.</p>
            <p>Salah satu alat paling fundamental adalah <em>BLAST</em> (Basic Local Alignment Search Tool) yang dikembangkan oleh Altschul dkk. BLAST memungkinkan peneliti mencari homologi antara sekuens query dengan jutaan sekuens dalam database seperti GenBank. Penyelarasan sekuens (sequence alignment), baik secara lokal maupun global, mengungkap hubungan evolusioner dan membantu mengidentifikasi domain fungsional pada protein yang belum dikarakterisasi.</p>
            <p>Proyek Genom Manusia (<em>Human Genome Project</em>) yang diselesaikan pada tahun 2003 berhasil memetakan sekitar 3 miliar pasang basa dan mengidentifikasi ~20.000–25.000 gen penyandi protein. Era pasca-genomik kini berfokus pada <em>genomika fungsional</em>, <em>transkriptomik</em>, dan <em>proteomik</em> untuk memahami bagaimana informasi genomik diterjemahkan menjadi fenotipe. Machine learning dan kecerdasan buatan semakin berperan dalam prediksi struktur protein (misalnya AlphaFold), penemuan obat, dan kedokteran presisi.</p>
        HTML;
    }

    private function articleContentPcr(): string
    {
        return <<<'HTML'
            <p><em>Polymerase Chain Reaction</em> (PCR) adalah teknik revolusioner yang ditemukan oleh Kary Mullis pada tahun 1983 untuk mengamplifikasi fragmen DNA spesifik secara eksponensial dari jumlah cetakan yang sangat sedikit. Prinsip dasar PCR melibatkan tiga langkah berulang: <em>denaturasi</em> (94–98 °C) untuk memisahkan untai ganda, <em>annealing</em> (50–65 °C) untuk menempelkan primer oligonukleotida ke sekuens target, dan <em>ekstensi</em> (72 °C) di mana DNA polimerase memperpanjang primer membentuk untai baru.</p>
            <p>Penggunaan <em>Taq polymerase</em> yang diisolasi dari bakteri termofilik <em>Thermus aquaticus</em> merupakan terobosan kunci karena enzim ini tetap aktif pada suhu tinggi selama siklus denaturasi. Setelah 30–40 siklus, jumlah fragmen DNA target meningkat secara eksponensial hingga miliaran kopi, memungkinkan deteksi dan analisis bahkan dari sampel forensik yang sangat sedikit.</p>
            <p>Variasi teknik PCR telah dikembangkan untuk berbagai aplikasi: <em>RT-PCR</em> untuk mengamplifikasi RNA (setelah transkripsi balik), <em>qPCR</em> (real-time PCR) untuk kuantifikasi absolut dan relatif ekspresi gen, <em>digital PCR</em> untuk sensitivitas deteksi tertinggi, dan <em>multiplex PCR</em> untuk mengamplifikasi beberapa target sekaligus. Dalam diagnosis molekuler, PCR digunakan secara luas untuk mendeteksi patogen (termasuk virus SARS-CoV-2), mengidentifikasi mutasi genetik penyebab penyakit, dan analisis DNA forensik dalam kriminologi.</p>
        HTML;
    }
}

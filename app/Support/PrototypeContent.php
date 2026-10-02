<?php

namespace App\Support;

use App\Enums\GoalVisibility;
use App\Enums\SeriesStatus;
use App\Enums\WatchableType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Hard-coded sample content for the design prototype.
 * Each method mirrors what a real query will return later, so the views
 * and components can be wired to models without changing their shape.
 *
 * @phpstan-type Chain array{slug: string, title: string, visibility: GoalVisibility, streak: int, bestStreak: int, days: list<'done'|'missed'|'excused'>, parent: ?string}
 * @phpstan-type YearlyGoal array{slug: string, title: string, type: 'numeric'|'milestones'|'binary', visibility: GoalVisibility, current: ?int, target: ?int, unit: ?string, milestones: list<array{title: string, done: bool}>, achievedAt: ?CarbonImmutable, parent: ?string, linkUrl: ?string}
 * @phpstan-type LongTermGoal array{slug: string, title: string, why: string, visibility: GoalVisibility, since: int}
 * @phpstan-type ReviewBlock array{type: 'paragraph'|'spoiler'|'quote', text: string, by?: string}
 * @phpstan-type RawWatchedEntry array{type: WatchableType, slug: string, title: string, originalTitle: ?string, year: int, creator: string, genres: list<string>, runtimeMinutes: ?int, overview: ?string, cast: list<array{name: string, role: string}>, watchedAt: CarbonImmutable, place: string, rating: ?float, isFavorite: bool, isRewatch: bool, status: ?SeriesStatus, season: ?int, episode: ?int, episodeCount: ?int, posterFile: string, posterColors: array{0: string, 1: string}, accent: string, review: ?list<ReviewBlock>}
 * @phpstan-type WatchedEntry array{type: WatchableType, slug: string, title: string, originalTitle: ?string, year: int, creator: string, genres: list<string>, runtimeMinutes: ?int, overview: ?string, cast: list<array{name: string, role: string}>, watchedAt: CarbonImmutable, place: string, rating: ?float, isFavorite: bool, isRewatch: bool, status: ?SeriesStatus, season: ?int, episode: ?int, episodeCount: ?int, posterFile: string, posterColors: array{0: string, 1: string}, accent: string, review: ?list<ReviewBlock>, posterUrl: ?string, hasReview: bool, reviewExcerpt: ?string, url: string}
 */
class PrototypeContent
{
    /**
     * @return array{title: string, slug: string, excerpt: string, publishedAt: CarbonImmutable, readingMinutes: int, tags: list<string>}
     */
    public static function latestPost(): array
    {
        return [
            'title' => 'Yapay zekâyla kod yazarken kendime koyduğum beş kural',
            'slug' => 'yapay-zekayla-kod-yazarken-bes-kural',
            'excerpt' => 'Asistan hızlı yazıyor, ama neyin doğru olduğuna hâlâ ben karar veriyorum. Bir yılın sonunda elimde kalan, biraz da acı tecrübeyle öğrendiğim beş kural.',
            'publishedAt' => CarbonImmutable::parse('2026-09-28'),
            'readingMinutes' => 6,
            'tags' => ['yapay zekâ', 'iş akışı'],
        ];
    }

    /**
     * The newest diary entry, for the home page.
     *
     * @return WatchedEntry
     */
    public static function lastWatched(): array
    {
        return self::watchedDiary()[0];
    }

    /**
     * Series on the "currently watching" shelf, most recently watched first.
     *
     * @return list<WatchedEntry>
     */
    public static function currentlyWatching(): array
    {
        return array_values(array_filter(
            self::watchedEntries(),
            fn (array $entry): bool => $entry['status']?->isInProgress() ?? false,
        ));
    }

    /**
     * Finished films and series (everything except the in-progress shelf), newest first.
     *
     * @return list<WatchedEntry>
     */
    public static function watchedDiary(): array
    {
        return array_values(array_filter(
            self::watchedEntries(),
            fn (array $entry): bool => ! ($entry['status']?->isInProgress() ?? false),
        ));
    }

    /**
     * @return WatchedEntry|null
     */
    public static function findWatched(WatchableType $type, string $slug): ?array
    {
        foreach (self::watchedEntries() as $entry) {
            if ($entry['type'] === $type && $entry['slug'] === $slug) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Every film and series, newest first, with derived fields added.
     * Facts (titles, overviews, cast) come from TMDB; ratings, dates and reviews are sample data.
     *
     * @return list<WatchedEntry>
     */
    public static function watchedEntries(): array
    {
        $entries = array_map(function (array $entry): array {
            $posterPath = '/images/prototype/posters/'.$entry['posterFile'];
            $firstParagraph = array_find($entry['review'] ?? [], fn (array $block): bool => $block['type'] === 'paragraph');

            return [
                ...$entry,
                'posterUrl' => file_exists(public_path($posterPath)) ? $posterPath : null,
                'hasReview' => $entry['review'] !== null,
                'reviewExcerpt' => $firstParagraph ? Str::limit($firstParagraph['text'], 180) : null,
                'url' => route('watched.show', ['type' => $entry['type']->routeSegment(), 'slug' => $entry['slug']]),
            ];
        }, self::rawWatchedEntries());

        usort($entries, fn (array $a, array $b): int => $b['watchedAt'] <=> $a['watchedAt']);

        return $entries;
    }

    /**
     * @return list<RawWatchedEntry>
     */
    private static function rawWatchedEntries(): array
    {
        return [
            [
                'type' => WatchableType::Film,
                'slug' => 'kuru-otlar-ustune',
                'title' => 'Kuru Otlar Üstüne',
                'originalTitle' => null,
                'year' => 2023,
                'creator' => 'Nuri Bilge Ceylan',
                'genres' => [
                    'Dram',
                ],
                'runtimeMinutes' => 197,
                'overview' => 'Genç bir öğretmen olan Samet, Doğu Anadolu\'da zorunlu görevini yapmaktadır. Onun en büyük hayali, zorunlu hizmetini tamamlamasının ardından İstanbul\'a tayin olmaktır. Ancak onun hayatı, meslektaşı Kenan ile bir kız öğrenci tarafından asılsız olarak tacizle suçlanmasıyla altüst olur. Kendisini bir anda büyük bir sıkıntının içinde bulan Samet için işler, kendisine yardımcı olabilecek meslektaşı Nuray ile tanışmasıyla değişir.',
                'cast' => [
                    [
                        'name' => 'Deniz Celiloğlu',
                        'role' => 'Samet',
                    ],
                    [
                        'name' => 'Merve Dizdar',
                        'role' => 'Nuray',
                    ],
                    [
                        'name' => 'Musab Ekici',
                        'role' => 'Kenan',
                    ],
                    [
                        'name' => 'Ece Bağcı',
                        'role' => 'Sevim',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-09-30'),
                'place' => 'evde',
                'rating' => 8.5,
                'isFavorite' => true,
                'isRewatch' => false,
                'status' => null,
                'season' => null,
                'episode' => null,
                'episodeCount' => null,
                'posterFile' => 'kuru-otlar-ustune.webp',
                'posterColors' => [
                    '#efbf80',
                    '#72460e',
                ],
                'accent' => '#f8d5a8',
                'review' => [
                    [
                        'type' => 'paragraph',
                        'text' => 'Nuri Bilge Ceylan yine üç saati aşan bir filmle karşımızda ve yine o süreyi hissettirmiyor. Doğu Anadolu\'da bir köy okulunda görev yapan Samet\'in tayin beklentisi, kıskançlığı ve kendine bile itiraf edemediği kırgınlıkları, karla kaplı bir manzaranın ortasında yavaş yavaş açılıyor.',
                    ],
                    [
                        'type' => 'paragraph',
                        'text' => 'Benim için filmin kalbi uzun akşam yemeği sahnesi. Samet ile Nuray\'ın konuşması bir fikir tartışması gibi başlıyor, sonra iki insanın birbirini ne kadar az tanıdığını gösteren bir aynaya dönüşüyor. Merve Dizdar\'ın oyunculuğu tek kelimeyle sarsıcı.',
                    ],
                    [
                        'type' => 'spoiler',
                        'text' => 'O akşamın ortasında Samet bir anlığına filmin setinden geçerek banyoya yürüyor; kameralar, ışıklar, ekip görünüyor. Ceylan bir an için bütün yanılsamayı kırıyor. İlk izleyişte şaşırtmıştı, şimdi filmin en dürüst anı olduğunu düşünüyorum.',
                    ],
                    [
                        'type' => 'paragraph',
                        'text' => 'Samet\'i sevmek zor, ama onda kendimden bir şeyler gördüm: kendini her zaman haklı çıkaran o iç ses. Film bittikten sonra bir süre sessiz oturdum. Bu benim için iyi bir filmin en güvenilir işareti.',
                    ],
                ],
            ],
            [
                'type' => WatchableType::Film,
                'slug' => 'perfect-days',
                'title' => 'Mükemmel Günler',
                'originalTitle' => 'Perfect Days',
                'year' => 2023,
                'creator' => 'Wim Wenders',
                'genres' => [
                    'Dram',
                ],
                'runtimeMinutes' => 125,
                'overview' => 'Tokyo’nun umumi tuvaletlerini temizleyen Hirayama, bir yandan müzik, edebiyat ve fotoğraf tutkusunun peşinden gittiği hayatından memnundur. Geçmişiyle yeniden bağ kurmasına yol açan beklenmedik karşılaşmalar, hayatının düzenini yavaş yavaş bozmaya başlar.',
                'cast' => [
                    [
                        'name' => 'Kōji Yakusho',
                        'role' => 'Hirayama',
                    ],
                    [
                        'name' => 'Tokio Emoto',
                        'role' => 'Takashi',
                    ],
                    [
                        'name' => 'Arisa Nakano',
                        'role' => 'Niko',
                    ],
                    [
                        'name' => 'Aoi Yamada',
                        'role' => 'Aya',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-09-21'),
                'place' => 'evde',
                'rating' => 9.0,
                'isFavorite' => true,
                'isRewatch' => false,
                'status' => null,
                'season' => null,
                'episode' => null,
                'episodeCount' => null,
                'posterFile' => 'perfect-days.webp',
                'posterColors' => [
                    '#d7bf98',
                    '#5c4624',
                ],
                'accent' => '#46361c',
                'review' => [
                    [
                        'type' => 'paragraph',
                        'text' => 'Tokyo\'da umumi tuvaletleri temizleyen bir adamın birbirine benzeyen günleri. Kâğıt üzerinde sıkıcı, ekranda ise yılın en huzurlu iki saati. Hirayama her sabah aynı saatte kalkıyor, bitkilerini suluyor, kasetini seçiyor ve işe gidiyor. Film bu tekrarı bir hapishane gibi değil, bir ritim gibi gösteriyor.',
                    ],
                    [
                        'type' => 'paragraph',
                        'text' => 'Zinciri kırmama takıntısı olan biri olarak bu filmi izlerken biraz da kendime baktım. Rutin insanı boğabilir de, ayakta da tutabilir. Fark, o rutinin içinde hâlâ bir şeyleri fark edip edemediğinde. Hirayama her gün aynı ağaçların fotoğrafını çekiyor ama hiçbir fotoğraf diğerine benzemiyor.',
                    ],
                    [
                        'type' => 'quote',
                        'text' => 'Bir dahaki sefer bir dahaki seferdir. Şimdi şimdidir.',
                        'by' => 'Hirayama',
                    ],
                    [
                        'type' => 'spoiler',
                        'text' => 'Son sahnede Hirayama arabasında Nina Simone\'un "Feeling Good" şarkısını dinlerken yüzünde aynı anda hem gülümseme hem gözyaşı var. Kamera kesmeden yüzünde kalıyor ve o birkaç dakikada filmin bütün sessizliği anlam kazanıyor.',
                    ],
                    [
                        'type' => 'paragraph',
                        'text' => 'Kısacası: herkese göre değil. Ama yavaş filmleri seviyorsanız ve bir süredir hiçbir şeyi gerçekten fark etmediğinizi düşünüyorsanız, bir akşamınızı buna ayırın.',
                    ],
                ],
            ],
            [
                'type' => WatchableType::Film,
                'slug' => 'dune-part-two',
                'title' => 'Dune: Çöl Gezegeni - Bölüm İki',
                'originalTitle' => 'Dune: Part Two',
                'year' => 2024,
                'creator' => 'Denis Villeneuve',
                'genres' => [
                    'Bilim-Kurgu',
                    'Macera',
                ],
                'runtimeMinutes' => 165,
                'overview' => 'Paul Atreides, Arrakis gezegeni için mücadeleye devam ediyor ve Fremen halkının liderliğini üstleniyor. Paul, Harkonnen ailesinin saldırısından kurtulduktan sonra, Fremenlerle birlikte yaşamaya başlar. Fremenlerin yardımıyla, Arrakis\'in kontrolüne yeniden sahip olmak ve evrenin kaderini değiştirmek için mücadele eder. Paul, Arrakis gezegeninin Fremen halkı tarafından Mesih olarak kabul edilir. Fremen\'ler, Paul\'ün liderliğinde Arrakis gezegenini özgürleştireceklerine ve galakside eşitlik ve adaleti sağlayacaklarına inanırlar.',
                'cast' => [
                    [
                        'name' => 'Timothée Chalamet',
                        'role' => 'Paul Atreides',
                    ],
                    [
                        'name' => 'Zendaya',
                        'role' => 'Chani',
                    ],
                    [
                        'name' => 'Rebecca Ferguson',
                        'role' => 'Jessica',
                    ],
                    [
                        'name' => 'Javier Bardem',
                        'role' => 'Stilgar',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-09-14'),
                'place' => 'evde, projeksiyonla',
                'rating' => 7.5,
                'isFavorite' => false,
                'isRewatch' => false,
                'status' => null,
                'season' => null,
                'episode' => null,
                'episodeCount' => null,
                'posterFile' => 'dune-part-two.webp',
                'posterColors' => [
                    '#ef8e80',
                    '#711a0e',
                ],
                'accent' => '#611308',
                'review' => null,
            ],
            [
                'type' => WatchableType::Series,
                'slug' => 'chernobyl',
                'title' => 'Chernobyl',
                'originalTitle' => null,
                'year' => 2019,
                'creator' => 'Craig Mazin',
                'genres' => [
                    'Dram',
                ],
                'runtimeMinutes' => null,
                'overview' => '1986 senesinde Sovyet nükleer santralinde meydana gelen patlama sonrası santral işçileri ve itfaiyeciler faciayı kontrol altına almaya çalışır.',
                'cast' => [
                    [
                        'name' => 'Jared Harris',
                        'role' => 'Valery Legasov',
                    ],
                    [
                        'name' => 'Stellan Skarsgård',
                        'role' => 'Boris Shcherbina',
                    ],
                    [
                        'name' => 'Emily Watson',
                        'role' => 'Ulana Khomyuk',
                    ],
                    [
                        'name' => 'Paul Ritter',
                        'role' => 'Anatoly Dyatlov',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-09-07'),
                'place' => 'evde',
                'rating' => 9.5,
                'isFavorite' => true,
                'isRewatch' => false,
                'status' => SeriesStatus::Finished,
                'season' => 1,
                'episode' => 5,
                'episodeCount' => 5,
                'posterFile' => 'chernobyl.webp',
                'posterColors' => [
                    '#acc4b4',
                    '#354a3c',
                ],
                'accent' => '#9eada3',
                'review' => [
                    [
                        'type' => 'paragraph',
                        'text' => 'Beş bölümlük bir felaket hikâyesi, ama asıl konusu radyasyon değil, yalan. Her bölümde biraz daha boğulduğumu hissettim; özellikle de söylenmesi gerekeni söylemekle bir sonraki tayini korumak arasında kalan memurları izlerken. Devlet dairesinde yedi yıl geçirmiş biri olarak bazı toplantı sahneleri fazla tanıdık geldi.',
                    ],
                    [
                        'type' => 'quote',
                        'text' => 'Yalanların bedeli nedir?',
                        'by' => 'Valeri Legasov',
                    ],
                ],
            ],
            [
                'type' => WatchableType::Film,
                'slug' => 'past-lives',
                'title' => 'Başka Bir Hayatta',
                'originalTitle' => 'Past Lives',
                'year' => 2023,
                'creator' => 'Celine Song',
                'genres' => [
                    'Dram',
                    'Romantik',
                ],
                'runtimeMinutes' => 106,
                'overview' => 'Birbirine derinden bağlı iki çocukluk arkadaşı olan Nora ve Hae Sung, Nora\'nın ailesi Güney Kore\'den göç edince ayrılmak zorunda kalır. Yirmi yıl sonra, kader ve aşk kavramlarıyla ve hayatı oluşturan seçimlerle yüzleşirken, önemli bir hafta boyunca New York\'ta yeniden bir araya gelirler.',
                'cast' => [
                    [
                        'name' => 'Greta Lee',
                        'role' => 'Nora',
                    ],
                    [
                        'name' => 'Teo Yoo',
                        'role' => 'Hae Sung',
                    ],
                    [
                        'name' => 'John Magaro',
                        'role' => 'Arthur',
                    ],
                    [
                        'name' => 'Moon Seung-ah',
                        'role' => 'Young Nora',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-09-03'),
                'place' => 'uçakta',
                'rating' => 8.0,
                'isFavorite' => false,
                'isRewatch' => false,
                'status' => null,
                'season' => null,
                'episode' => null,
                'episodeCount' => null,
                'posterFile' => 'past-lives.webp',
                'posterColors' => [
                    '#adb8c2',
                    '#364049',
                ],
                'accent' => '#3c4044',
                'review' => null,
            ],
            [
                'type' => WatchableType::Film,
                'slug' => 'anatomy-of-a-fall',
                'title' => 'Bir Düşüşün Anatomisi',
                'originalTitle' => 'Anatomie d\'une chute',
                'year' => 2023,
                'creator' => 'Justine Triet',
                'genres' => [
                    'Gerilim',
                    'Gizem',
                    'Suç',
                ],
                'runtimeMinutes' => 151,
                'overview' => 'Dünya prömiyerini yaptığı Cannes Film Festivali’nde Altın Palmiye\'nin sahibi olan bu "Hitchcockvari mahkeme filmi" bir evliliğin dinamiklerini mercek altına yatıran bir psikolojik gerilim. "Birinin özel hayatı başkasının cehennemidir" fikrinden yola çıkan Bir Düşüşün Anatomisi, Fransız Alpleri\'nde bir kulübede kocası Samuel ve görme engelli oğluyla izole bir yaşam süren Alman yazar Sandra\'yı izliyor. Samuel yüksekten düşerek ölür fakat soruşturma sonucunda ölüm nedeninin intihar mı kaza mı olduğu kesinleşmeyince Sandra cinayet suçlamasıyla tutuklanır. Samuel\'in ölümünün sorgulandığı mahkeme süreci, çiftin çalkantılı ilişkilerinin de derinine inen rahatsız edici ve tatsız bir psikolojik yolculuğa dönüşür.',
                'cast' => [
                    [
                        'name' => 'Sandra Hüller',
                        'role' => 'Sandra Voyter',
                    ],
                    [
                        'name' => 'Swann Arlaud',
                        'role' => 'Maître Vincent Renzi',
                    ],
                    [
                        'name' => 'Milo Machado-Graner',
                        'role' => 'Daniel',
                    ],
                    [
                        'name' => 'Antoine Reinartz',
                        'role' => 'Advocate General',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-08-29'),
                'place' => 'sinemada, yazlık gösterim',
                'rating' => 8.0,
                'isFavorite' => false,
                'isRewatch' => false,
                'status' => null,
                'season' => null,
                'episode' => null,
                'episodeCount' => null,
                'posterFile' => 'anatomy-of-a-fall.webp',
                'posterColors' => [
                    '#a5adcb',
                    '#2f3651',
                ],
                'accent' => '#5e698f',
                'review' => [
                    [
                        'type' => 'paragraph',
                        'text' => 'Bir mahkeme filmi olarak başlıyor, bir evlilik filmine dönüşüyor. Sandra\'nın suçlu olup olmadığını film hiçbir zaman tam olarak söylemiyor ve bence en güçlü yanı da bu. Ses kaydı üzerinden dinlediğimiz kavga, son yıllarda izlediğim en gerçekçi tartışma.',
                    ],
                ],
            ],
            [
                'type' => WatchableType::Film,
                'slug' => 'the-zone-of-interest',
                'title' => 'İlgi Alanı',
                'originalTitle' => 'The Zone of Interest',
                'year' => 2023,
                'creator' => 'Jonathan Glazer',
                'genres' => [
                    'Dram',
                    'Tarih',
                    'Savaş',
                ],
                'runtimeMinutes' => 105,
                'overview' => 'Auschwitz kumandanı Rudolf Höss, eşi Hedwig, çocukları ve hizmetkârlarıyla rüya gibi bir hayat sürmektedir. Öyle ki, ölüm kampının duvarına bakan muhteşem evleri tam da tren raylarıyla gaz odaları arasındadır. Martin Amis\'in aynı adlı romanından uyarlanan bu çarpıcı film çiçekli, geniş bahçeleri, seraları ve havuzlarında keyif süren Höss ailesinin başlarına ölüm külleri serpilirken süregiden sıradan gündelik yaşamını gözlemliyor.',
                'cast' => [
                    [
                        'name' => 'Christian Friedel',
                        'role' => 'Rudolf Höss',
                    ],
                    [
                        'name' => 'Sandra Hüller',
                        'role' => 'Hedwig Höss',
                    ],
                    [
                        'name' => 'Johann Karthaus',
                        'role' => 'Claus Höss',
                    ],
                    [
                        'name' => 'Luis Noah Witte',
                        'role' => 'Hans Höss',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-08-22'),
                'place' => 'evde',
                'rating' => 7.0,
                'isFavorite' => false,
                'isRewatch' => false,
                'status' => null,
                'season' => null,
                'episode' => null,
                'episodeCount' => null,
                'posterFile' => 'the-zone-of-interest.webp',
                'posterColors' => [
                    '#9cd4a2',
                    '#27592d',
                ],
                'accent' => '#2c5f32',
                'review' => null,
            ],
            [
                'type' => WatchableType::Series,
                'slug' => 'lost',
                'title' => 'Lost',
                'originalTitle' => null,
                'year' => 2004,
                'creator' => 'J.J. Abrams',
                'genres' => [
                    'Gizem',
                    'Aksiyon & Macera',
                    'Dram',
                ],
                'runtimeMinutes' => null,
                'overview' => 'Oceanic Havayolları’nın Sidney-Los Angeles seferini yapan 815 sefer sayılı uçağı, okyanus üzerinden geçerken, manyetik bir alana kapılarak büyük bir adaya düşer. Fakat önceleri sıradan, tropik bir ada gibi görünen bu kara parçasının, kazazedelerin her birinin hayatını farklı biçimde değiştireceğinden habersizdirler.',
                'cast' => [
                    [
                        'name' => 'Matthew Fox',
                        'role' => 'Jack Shephard',
                    ],
                    [
                        'name' => 'Evangeline Lilly',
                        'role' => 'Kate Austen',
                    ],
                    [
                        'name' => 'Terry O\'Quinn',
                        'role' => 'John Locke',
                    ],
                    [
                        'name' => 'Josh Holloway',
                        'role' => 'James Sawyer',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-08-15'),
                'place' => 'evde',
                'rating' => 6.0,
                'isFavorite' => false,
                'isRewatch' => false,
                'status' => SeriesStatus::Dropped,
                'season' => 3,
                'episode' => 7,
                'episodeCount' => 23,
                'posterFile' => 'lost.webp',
                'posterColors' => [
                    '#9fd1d1',
                    '#295656',
                ],
                'accent' => '#213f3f',
                'review' => null,
            ],
            [
                'type' => WatchableType::Film,
                'slug' => 'kis-uykusu',
                'title' => 'Kış Uykusu',
                'originalTitle' => null,
                'year' => 2014,
                'creator' => 'Nuri Bilge Ceylan',
                'genres' => [
                    'Dram',
                ],
                'runtimeMinutes' => 196,
                'overview' => 'Aydın emekli bir tiyatrocudur; oyunculuğu bıraktıktan sonra Kapadokya\'ya babasından yadigar kalan butik oteli işletmek için geri döner. Aydın o günden sonra başlayan kış uykusu bu gözlerden ırak otelin içerisindeki gündelikleriyle, kâh yerel bir gazeteye köşe yazıları yazarak kâh her zaman niyetlendiği ancak bir türlü başlayamadığı tiyatro tarihi kitabını yazmayı düşünerek geçer. Tüm bu süreçte hayatında iki kadın vardır: Kendisine her anlamda uzak ve soğuk davranan genç karısı Nihal ve boşandıktan sonra yanlarına taşınan kız kardeşi Necla... Kışın bastırması ve artan kar yağışı bu küçük taşrada en çok Aydın\'ın sinirlerine dokunur ve onu uzaklara gitmeye teşvik eder...',
                'cast' => [
                    [
                        'name' => 'Haluk Bilginer',
                        'role' => 'Aydın',
                    ],
                    [
                        'name' => 'Melisa Sözen',
                        'role' => 'Nihal',
                    ],
                    [
                        'name' => 'Demet Akbağ',
                        'role' => 'Necla',
                    ],
                    [
                        'name' => 'Ayberk Pekcan',
                        'role' => 'Hidayet',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-08-09'),
                'place' => 'evde',
                'rating' => 9.0,
                'isFavorite' => true,
                'isRewatch' => true,
                'status' => null,
                'season' => null,
                'episode' => null,
                'episodeCount' => null,
                'posterFile' => 'kis-uykusu.webp',
                'posterColors' => [
                    '#cab4a5',
                    '#503c2f',
                ],
                'accent' => '#cbbcb2',
                'review' => null,
            ],
            [
                'type' => WatchableType::Film,
                'slug' => 'oppenheimer',
                'title' => 'Oppenheimer',
                'originalTitle' => null,
                'year' => 2023,
                'creator' => 'Christopher Nolan',
                'genres' => [
                    'Dram',
                    'Tarih',
                ],
                'runtimeMinutes' => 181,
                'overview' => 'İkinci Dünya Savaşı sırasında atom bombasının geliştirilmesine liderlik eden Amerikalı fizikçi J. Robert Oppenheimer\'ın hikayesi. Manhattan Projesi\'nin başına geçen Oppenheimer, insanlık tarihini değiştirecek bu ölümcül icadı hayata geçirirken, yarattığı gücün doğuracağı vicdani ve siyasi sonuçlarla da yüzleşmek zorunda kalır.',
                'cast' => [
                    [
                        'name' => 'Cillian Murphy',
                        'role' => 'J. Robert Oppenheimer',
                    ],
                    [
                        'name' => 'Emily Blunt',
                        'role' => 'Kitty Oppenheimer',
                    ],
                    [
                        'name' => 'Matt Damon',
                        'role' => 'Leslie Groves',
                    ],
                    [
                        'name' => 'Robert Downey Jr.',
                        'role' => 'Lewis Strauss',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-08-04'),
                'place' => 'evde',
                'rating' => 7.5,
                'isFavorite' => false,
                'isRewatch' => false,
                'status' => null,
                'season' => null,
                'episode' => null,
                'episodeCount' => null,
                'posterFile' => 'oppenheimer.webp',
                'posterColors' => [
                    '#e79f88',
                    '#6a2a15',
                ],
                'accent' => '#5c2210',
                'review' => null,
            ],
            [
                'type' => WatchableType::Series,
                'slug' => 'bir-baskadir',
                'title' => 'Bir Başkadır',
                'originalTitle' => null,
                'year' => 2020,
                'creator' => 'Berkun Oya',
                'genres' => [
                    'Dram',
                    'Gizem',
                ],
                'runtimeMinutes' => null,
                'overview' => 'Dizi, birbirlerinden oldukça farklı karakterde olan ve bambaşka hayatlar yaşayan bir grup insanın yollarının kesişmesiyle değişen yaşamlarını konu ediyor. Bu karakterler ya yeni bir yola yürümek ya da karmaşık bir geçmişle hesaplaşmak zorunda kalacaklardır.',
                'cast' => [
                    [
                        'name' => 'Öykü Karayel',
                        'role' => 'Meryem',
                    ],
                    [
                        'name' => 'Funda Eryiğit',
                        'role' => 'Ruhiye',
                    ],
                    [
                        'name' => 'Fatih Artman',
                        'role' => 'Yasin',
                    ],
                    [
                        'name' => 'Defne Kayalar',
                        'role' => 'Dr. Peri Aksoy',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-08-01'),
                'place' => 'evde',
                'rating' => 9.0,
                'isFavorite' => true,
                'isRewatch' => true,
                'status' => SeriesStatus::Finished,
                'season' => 1,
                'episode' => 8,
                'episodeCount' => 8,
                'posterFile' => 'bir-baskadir.webp',
                'posterColors' => [
                    '#dbb894',
                    '#5f4020',
                ],
                'accent' => '#cc9b69',
                'review' => null,
            ],
            [
                'type' => WatchableType::Series,
                'slug' => 'severance',
                'title' => 'Severance',
                'originalTitle' => null,
                'year' => 2022,
                'creator' => 'Dan Erickson',
                'genres' => [
                    'Dram',
                    'Gizem',
                    'Bilim Kurgu & Fantazi',
                ],
                'runtimeMinutes' => null,
                'overview' => 'Mark, anıları cerrahi müdahaleyle iş ve özel hayat olarak ayrılmış bir ofis çalışanı ekibinin başındadır. Gizemli bir iş arkadaşı ona iş yeri dışında göründüğünde, yaptıkları iş hakkındaki gerçeği keşfetmek için bir yolculuk başlar.',
                'cast' => [
                    [
                        'name' => 'Adam Scott',
                        'role' => 'Mark Scout',
                    ],
                    [
                        'name' => 'Britt Lower',
                        'role' => 'Helly Riggs',
                    ],
                    [
                        'name' => 'Tramell Tillman',
                        'role' => 'Seth Milchick',
                    ],
                    [
                        'name' => 'Zach Cherry',
                        'role' => 'Dylan George',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-09-26'),
                'place' => 'evde',
                'rating' => null,
                'isFavorite' => false,
                'isRewatch' => false,
                'status' => SeriesStatus::Watching,
                'season' => 2,
                'episode' => 5,
                'episodeCount' => 10,
                'posterFile' => 'severance.webp',
                'posterColors' => [
                    '#93c4dc',
                    '#1f4b60',
                ],
                'accent' => '#9fcbe0',
                'review' => null,
            ],
            [
                'type' => WatchableType::Series,
                'slug' => 'the-bear',
                'title' => 'The Bear',
                'originalTitle' => null,
                'year' => 2022,
                'creator' => 'Christopher Storer',
                'genres' => [
                    'Dram',
                    'Komedi',
                ],
                'runtimeMinutes' => null,
                'overview' => 'Gastronomi dünyasından genç bir şef olan Carmen "Carmy" Berzatto, yürek burkan bir ölümün ardından ailesinin sandviç dükkanını işletmek için Chicago\'ya dönmek zorunda kalır. Kendi dünyasından uzakta olan Carmy, bir trajedinin sonuçlarına katlanırken küçük bir işletmenin, inatçı bir personelin ve gergin aile ilişkilerinin ezici sorumluluklarıyla uğraşmak zorundadır.',
                'cast' => [
                    [
                        'name' => 'Jeremy Allen White',
                        'role' => 'Carmen \'Carmy\' Berzatto',
                    ],
                    [
                        'name' => 'Ebon Moss-Bachrach',
                        'role' => 'Richard \'Richie\' Jerimovich',
                    ],
                    [
                        'name' => 'Ayo Edebiri',
                        'role' => 'Sydney Adamu',
                    ],
                    [
                        'name' => 'Lionel Boyce',
                        'role' => 'Marcus Brooks',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-09-24'),
                'place' => 'evde',
                'rating' => null,
                'isFavorite' => false,
                'isRewatch' => false,
                'status' => SeriesStatus::Watching,
                'season' => 3,
                'episode' => 2,
                'episodeCount' => 10,
                'posterFile' => 'the-bear.webp',
                'posterColors' => [
                    '#8ba4e4',
                    '#182e67',
                ],
                'accent' => '#122556',
                'review' => null,
            ],
            [
                'type' => WatchableType::Series,
                'slug' => 'shogun',
                'title' => 'Shōgun',
                'originalTitle' => null,
                'year' => 2024,
                'creator' => 'Rachel Kondo',
                'genres' => [
                    'Dram',
                    'Savaş & Politik',
                ],
                'runtimeMinutes' => null,
                'overview' => 'Japonya\'da 1600 yılında geçen hikayede Lord Yoshii Toranaga, yakınlardaki bir balıkçı köyünde gizemli bir Avrupa gemisi karaya oturduğunda, Naipler Konseyi\'ndeki düşmanları ona karşı birleştiği için hayatı uğruna savaşmaktadır.',
                'cast' => [
                    [
                        'name' => 'Hiroyuki Sanada',
                        'role' => 'Yoshii Toranaga',
                    ],
                    [
                        'name' => 'Cosmo Jarvis',
                        'role' => 'John Blackthorne',
                    ],
                    [
                        'name' => 'Anna Sawai',
                        'role' => 'Toda Mariko',
                    ],
                    [
                        'name' => 'Tadanobu Asano',
                        'role' => 'Kashigi Yabushige',
                    ],
                ],
                'watchedAt' => CarbonImmutable::parse('2026-07-18'),
                'place' => 'evde',
                'rating' => null,
                'isFavorite' => false,
                'isRewatch' => false,
                'status' => SeriesStatus::Paused,
                'season' => 1,
                'episode' => 4,
                'episodeCount' => 10,
                'posterFile' => 'shogun.webp',
                'posterColors' => [
                    '#97d7d8',
                    '#235c5d',
                ],
                'accent' => '#225758',
                'review' => null,
            ],
        ];
    }

    /**
     * Chains for the home page: the last 14 days of every visible chain.
     *
     * @return list<Chain>
     */
    public static function activeChains(): array
    {
        return array_map(
            fn (array $chain): array => [...$chain, 'days' => array_slice($chain['days'], -14)],
            self::chains(),
        );
    }

    /**
     * Daily chains with the last 21 days, oldest first. Hidden chains are left out,
     * the way a visibility scope will do it later.
     *
     * @return list<Chain>
     */
    public static function chains(): array
    {
        return self::visibleOnly([
            [
                'slug' => 'her-gun-kod',
                'title' => 'Her gün 30 dk kod',
                'visibility' => GoalVisibility::Public,
                'streak' => 23,
                'bestStreak' => 58,
                'days' => self::days('xxxxxxxxxxxxexxxxxxxx'),
                'parent' => 'kendi-urunum',
            ],
            [
                'slug' => 'spor',
                'title' => 'Spor',
                'visibility' => GoalVisibility::Public,
                'streak' => 6,
                'bestStreak' => 19,
                'days' => self::days('xxxxx-xxx-xxxx-xxxxxx'),
                'parent' => 'formda-50',
            ],
            [
                'slug' => 'almanca-okuma',
                'title' => 'Her gün 10 sayfa Almanca',
                'visibility' => GoalVisibility::Public,
                'streak' => 2,
                'bestStreak' => 12,
                'days' => self::days('xxx-xxxxxx--xxxxxx-xx'),
                'parent' => 'almanca-c1',
            ],
            [
                'slug' => 'gizli-zincir-1',
                'title' => 'Ekransız sabahlar',
                'visibility' => GoalVisibility::Censored,
                'streak' => 41,
                'bestStreak' => 41,
                'days' => self::days('xxxxxxxxxxxxxxxxxxxxx'),
                'parent' => null,
            ],
            [
                'slug' => 'gizli-zincir-2',
                'title' => 'Tamamen gizli bir alışkanlık',
                'visibility' => GoalVisibility::Hidden,
                'streak' => 9,
                'bestStreak' => 9,
                'days' => self::days('xxxxxxxxxxxxxxxxxxxxx'),
                'parent' => null,
            ],
        ]);
    }

    /**
     * Goals for the current year, in three shapes: numeric, milestones and yes/no.
     *
     * @return list<YearlyGoal>
     */
    public static function yearlyGoals(): array
    {
        $goal = self::yearlyGoal(...);

        return self::visibleOnly([
            $goal([
                'slug' => 'comon-yayinla',
                'title' => "CoMon'u herkese açık yayınla",
                'type' => 'milestones',
                'visibility' => GoalVisibility::Public,
                'milestones' => [
                    ['title' => 'Kapalı beta', 'done' => true],
                    ['title' => 'Hane üyeleri ve davet sistemi', 'done' => true],
                    ['title' => 'Sayaç okumaları için grafikler', 'done' => true],
                    ['title' => 'Mobil uyum', 'done' => false],
                    ['title' => 'Herkese açık yayın', 'done' => false],
                ],
                'parent' => 'kendi-urunum',
                'linkUrl' => route('projects.index'),
            ]),
            $goal([
                'slug' => '12-kitap',
                'title' => '12 kitap oku',
                'type' => 'numeric',
                'visibility' => GoalVisibility::Public,
                'current' => 10,
                'target' => 12,
                'unit' => 'kitap',
            ]),
            $goal([
                'slug' => '500-km',
                'title' => '500 km koş',
                'type' => 'numeric',
                'visibility' => GoalVisibility::Public,
                'current' => 362,
                'target' => 500,
                'unit' => 'km',
                'parent' => 'formda-50',
            ]),
            $goal([
                'slug' => '24-yazi',
                'title' => '24 blog yazısı yayınla',
                'type' => 'numeric',
                'visibility' => GoalVisibility::Public,
                'current' => 9,
                'target' => 24,
                'unit' => 'yazı',
                'parent' => 'turkce-icerik',
            ]),
            $goal([
                'slug' => 'almanca-c1',
                'title' => 'Almanca C1 sınavını geç',
                'type' => 'binary',
                'visibility' => GoalVisibility::Public,
                'parent' => 'almanca',
            ]),
            $goal([
                'slug' => 'meetup-konusmasi',
                'title' => "Bir Laravel meetup'ında konuşma yap",
                'type' => 'binary',
                'visibility' => GoalVisibility::Public,
                'achievedAt' => CarbonImmutable::parse('2026-06-12'),
                'parent' => 'turkce-icerik',
            ]),
            $goal([
                'slug' => 'gizli-yillik-1',
                'title' => 'Kimseye söylemediğim bir hedef',
                'type' => 'numeric',
                'visibility' => GoalVisibility::Censored,
                'current' => 3,
                'target' => 10,
                'unit' => '',
            ]),
        ]);
    }

    /**
     * Fills in the optional fields of a yearly goal.
     *
     * @param  array{slug: string, title: string, type: 'numeric'|'milestones'|'binary', visibility: GoalVisibility, current?: int, target?: int, unit?: string, milestones?: list<array{title: string, done: bool}>, achievedAt?: CarbonImmutable, parent?: string, linkUrl?: string}  $attributes
     * @return YearlyGoal
     */
    private static function yearlyGoal(array $attributes): array
    {
        return [
            'current' => null,
            'target' => null,
            'unit' => null,
            'milestones' => [],
            'achievedAt' => null,
            'parent' => null,
            'linkUrl' => null,
            ...$attributes,
        ];
    }

    /**
     * Long-term goals: no progress bar, a reason and a story instead.
     *
     * @return list<LongTermGoal>
     */
    public static function longTermGoals(): array
    {
        return self::visibleOnly([
            [
                'slug' => 'kendi-urunum',
                'title' => 'Kendi ürünümü çıkarmak',
                'why' => 'Başkasının fikrini değil, kendi fikrimi büyütmek istiyorum. İnsanların gerçekten kullandığı küçük ama dürüst bir ürün.',
                'visibility' => GoalVisibility::Public,
                'since' => 2024,
            ],
            [
                'slug' => 'almanca',
                'title' => 'Almancayı Türkçe kadar rahat konuşmak',
                'why' => 'Bir toplantıda kelime aramadan, esprimi çevirmeden konuşabildiğim gün burası gerçekten evim olacak.',
                'visibility' => GoalVisibility::Public,
                'since' => 2016,
            ],
            [
                'slug' => 'formda-50',
                'title' => '50 yaşına formda girmek',
                'why' => 'Masa başında geçen bir meslekte vücudumu ihmal etmemek. Yaşlandıkça da dağ yürüyüşüne çıkabilen biri olmak.',
                'visibility' => GoalVisibility::Public,
                'since' => 2025,
            ],
            [
                'slug' => 'turkce-icerik',
                'title' => 'Türkçe teknik içerik üreten biri olmak',
                'why' => 'Ben öğrenirken Türkçe kaynak çok azdı. Benden sonra gelenler için o eksikliği biraz kapatmak istiyorum.',
                'visibility' => GoalVisibility::Public,
                'since' => 2026,
            ],
            [
                'slug' => 'gizli-uzun-1',
                'title' => 'Çok kişisel bir hedef',
                'why' => 'Bunun nedenini sadece ben biliyorum ve şimdilik öyle kalsın.',
                'visibility' => GoalVisibility::Censored,
                'since' => 2023,
            ],
        ]);
    }

    /**
     * Last years' goals, kept honestly: the ones that did not happen are scribbled over, not deleted.
     *
     * @return array<int, list<array{title: string, achieved: bool}>>
     */
    public static function pastYearGoals(): array
    {
        return [
            2025 => [
                ['title' => 'Fachinformatiker sınavını geç', 'achieved' => true],
                ['title' => '10 kitap oku', 'achieved' => true],
                ['title' => 'Yarı maraton koş', 'achieved' => false],
                ['title' => 'Her ay bir yan proje bitir', 'achieved' => false],
                ['title' => 'İlk açık kaynak katkımı yap', 'achieved' => true],
            ],
        ];
    }

    /**
     * @template T of array{visibility: GoalVisibility}
     *
     * @param  list<T>  $goals
     * @return list<T>
     */
    private static function visibleOnly(array $goals): array
    {
        return array_values(array_filter($goals, fn (array $goal): bool => $goal['visibility']->isVisible()));
    }

    /**
     * Turns a compact day string into chain days: x = done, - = missed, e = excused.
     *
     * @return list<'done'|'missed'|'excused'>
     */
    private static function days(string $pattern): array
    {
        return array_map(fn (string $day): string => match ($day) {
            'x' => 'done',
            'e' => 'excused',
            default => 'missed',
        }, str_split($pattern));
    }

    /**
     * @return array{name: string, slug: string, tagline: string, status: 'in-progress'|'live'|'archived', imageUrl: string, demoUrl: string, stack: list<string>, latestLog: array{date: CarbonImmutable, text: string}}
     */
    public static function featuredProject(): array
    {
        return [
            'name' => 'CoMon',
            'slug' => 'comon',
            'tagline' => 'Sözleşmeleri, sayaç okumalarını ve ev bütçesini tek yerde tutan, çok kullanıcılı bir ev yönetimi uygulaması.',
            'status' => 'in-progress',
            'imageUrl' => '/images/projects/comon.webp',
            'demoUrl' => 'https://comon.guelec.eu',
            'stack' => ['Laravel', 'Livewire', 'Alpine.js', 'MySQL'],
            'latestLog' => [
                'date' => CarbonImmutable::parse('2026-09-24'),
                'text' => 'Sayaç okumalarına aylık tüketim grafiği eklendi.',
            ],
        ];
    }
}

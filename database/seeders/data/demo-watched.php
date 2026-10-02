<?php

/*
 * Sample films and series of the design prototype, for local development only (DemoSeeder).
 * Facts come from TMDB; ratings, dates and reviews are sample data. Posters are read from
 * public/images/prototype/posters when present (not in git, for copyright reasons).
 */

return [
    0 => [
        'type' => 'film',
        'slug' => 'kuru-otlar-ustune',
        'title' => 'Kuru Otlar Üstüne',
        'original_title' => null,
        'year' => 2023,
        'creator' => 'Nuri Bilge Ceylan',
        'genres' => [
            0 => 'Dram',
        ],
        'runtime_minutes' => 197,
        'overview' => 'Genç bir öğretmen olan Samet, Doğu Anadolu\'da zorunlu görevini yapmaktadır. Onun en büyük hayali, zorunlu hizmetini tamamlamasının ardından İstanbul\'a tayin olmaktır. Ancak onun hayatı, meslektaşı Kenan ile bir kız öğrenci tarafından asılsız olarak tacizle suçlanmasıyla altüst olur. Kendisini bir anda büyük bir sıkıntının içinde bulan Samet için işler, kendisine yardımcı olabilecek meslektaşı Nuray ile tanışmasıyla değişir.',
        'cast' => [
            0 => [
                'name' => 'Deniz Celiloğlu',
                'role' => 'Samet',
            ],
            1 => [
                'name' => 'Merve Dizdar',
                'role' => 'Nuray',
            ],
            2 => [
                'name' => 'Musab Ekici',
                'role' => 'Kenan',
            ],
            3 => [
                'name' => 'Ece Bağcı',
                'role' => 'Sevim',
            ],
        ],
        'watched_on' => '2026-09-30',
        'place' => 'evde',
        'rating' => 8.5,
        'is_favorite' => true,
        'is_rewatch' => false,
        'series_status' => null,
        'current_season' => null,
        'current_episode' => null,
        'seasons' => [
        ],
        'poster_file' => 'kuru-otlar-ustune.webp',
        'poster_colors' => [
            0 => '#efbf80',
            1 => '#72460e',
        ],
        'accent' => '#f8d5a8',
        'review' => 'Nuri Bilge Ceylan yine üç saati aşan bir filmle karşımızda ve yine o süreyi hissettirmiyor. Doğu Anadolu\'da bir köy okulunda görev yapan Samet\'in tayin beklentisi, kıskançlığı ve kendine bile itiraf edemediği kırgınlıkları, karla kaplı bir manzaranın ortasında yavaş yavaş açılıyor.

Benim için filmin kalbi uzun akşam yemeği sahnesi. Samet ile Nuray\'ın konuşması bir fikir tartışması gibi başlıyor, sonra iki insanın birbirini ne kadar az tanıdığını gösteren bir aynaya dönüşüyor. Merve Dizdar\'ın oyunculuğu tek kelimeyle sarsıcı.

:::spoiler
O akşamın ortasında Samet bir anlığına filmin setinden geçerek banyoya yürüyor; kameralar, ışıklar, ekip görünüyor. Ceylan bir an için bütün yanılsamayı kırıyor. İlk izleyişte şaşırtmıştı, şimdi filmin en dürüst anı olduğunu düşünüyorum.
:::

Samet\'i sevmek zor, ama onda kendimden bir şeyler gördüm: kendini her zaman haklı çıkaran o iç ses. Film bittikten sonra bir süre sessiz oturdum. Bu benim için iyi bir filmin en güvenilir işareti.',
    ],
    1 => [
        'type' => 'film',
        'slug' => 'perfect-days',
        'title' => 'Mükemmel Günler',
        'original_title' => 'Perfect Days',
        'year' => 2023,
        'creator' => 'Wim Wenders',
        'genres' => [
            0 => 'Dram',
        ],
        'runtime_minutes' => 125,
        'overview' => 'Tokyo\'nun umumi tuvaletlerini temizleyen Hirayama, bir yandan müzik, edebiyat ve fotoğraf tutkusunun peşinden gittiği hayatından memnundur. Geçmişiyle yeniden bağ kurmasına yol açan beklenmedik karşılaşmalar, hayatının düzenini yavaş yavaş bozmaya başlar.',
        'cast' => [
            0 => [
                'name' => 'Kōji Yakusho',
                'role' => 'Hirayama',
            ],
            1 => [
                'name' => 'Tokio Emoto',
                'role' => 'Takashi',
            ],
            2 => [
                'name' => 'Arisa Nakano',
                'role' => 'Niko',
            ],
            3 => [
                'name' => 'Aoi Yamada',
                'role' => 'Aya',
            ],
        ],
        'watched_on' => '2026-09-21',
        'place' => 'evde',
        'rating' => 9.0,
        'is_favorite' => true,
        'is_rewatch' => false,
        'series_status' => null,
        'current_season' => null,
        'current_episode' => null,
        'seasons' => [
        ],
        'poster_file' => 'perfect-days.webp',
        'poster_colors' => [
            0 => '#d7bf98',
            1 => '#5c4624',
        ],
        'accent' => '#46361c',
        'review' => 'Tokyo\'da umumi tuvaletleri temizleyen bir adamın birbirine benzeyen günleri. Kâğıt üzerinde sıkıcı, ekranda ise yılın en huzurlu iki saati. Hirayama her sabah aynı saatte kalkıyor, bitkilerini suluyor, kasetini seçiyor ve işe gidiyor. Film bu tekrarı bir hapishane gibi değil, bir ritim gibi gösteriyor.

Zinciri kırmama takıntısı olan biri olarak bu filmi izlerken biraz da kendime baktım. Rutin insanı boğabilir de, ayakta da tutabilir. Fark, o rutinin içinde hâlâ bir şeyleri fark edip edemediğinde. Hirayama her gün aynı ağaçların fotoğrafını çekiyor ama hiçbir fotoğraf diğerine benzemiyor.

:::replik Hirayama
Bir dahaki sefer bir dahaki seferdir. Şimdi şimdidir.
:::

:::spoiler
Son sahnede Hirayama arabasında Nina Simone\'un "Feeling Good" şarkısını dinlerken yüzünde aynı anda hem gülümseme hem gözyaşı var. Kamera kesmeden yüzünde kalıyor ve o birkaç dakikada filmin bütün sessizliği anlam kazanıyor.
:::

Kısacası: herkese göre değil. Ama yavaş filmleri seviyorsanız ve bir süredir hiçbir şeyi gerçekten fark etmediğinizi düşünüyorsanız, bir akşamınızı buna ayırın.',
    ],
    2 => [
        'type' => 'film',
        'slug' => 'dune-part-two',
        'title' => 'Dune: Çöl Gezegeni - Bölüm İki',
        'original_title' => 'Dune: Part Two',
        'year' => 2024,
        'creator' => 'Denis Villeneuve',
        'genres' => [
            0 => 'Bilim-Kurgu',
            1 => 'Macera',
        ],
        'runtime_minutes' => 165,
        'overview' => 'Paul Atreides, Arrakis gezegeni için mücadeleye devam ediyor ve Fremen halkının liderliğini üstleniyor. Paul, Harkonnen ailesinin saldırısından kurtulduktan sonra, Fremenlerle birlikte yaşamaya başlar. Fremenlerin yardımıyla, Arrakis\'in kontrolüne yeniden sahip olmak ve evrenin kaderini değiştirmek için mücadele eder. Paul, Arrakis gezegeninin Fremen halkı tarafından Mesih olarak kabul edilir. Fremen\'ler, Paul\'ün liderliğinde Arrakis gezegenini özgürleştireceklerine ve galakside eşitlik ve adaleti sağlayacaklarına inanırlar.',
        'cast' => [
            0 => [
                'name' => 'Timothée Chalamet',
                'role' => 'Paul Atreides',
            ],
            1 => [
                'name' => 'Zendaya',
                'role' => 'Chani',
            ],
            2 => [
                'name' => 'Rebecca Ferguson',
                'role' => 'Jessica',
            ],
            3 => [
                'name' => 'Javier Bardem',
                'role' => 'Stilgar',
            ],
        ],
        'watched_on' => '2026-09-14',
        'place' => 'evde, projeksiyonla',
        'rating' => 7.5,
        'is_favorite' => false,
        'is_rewatch' => false,
        'series_status' => null,
        'current_season' => null,
        'current_episode' => null,
        'seasons' => [
        ],
        'poster_file' => 'dune-part-two.webp',
        'poster_colors' => [
            0 => '#ef8e80',
            1 => '#711a0e',
        ],
        'accent' => '#611308',
        'review' => null,
    ],
    3 => [
        'type' => 'series',
        'slug' => 'chernobyl',
        'title' => 'Chernobyl',
        'original_title' => null,
        'year' => 2019,
        'creator' => 'Craig Mazin',
        'genres' => [
            0 => 'Dram',
        ],
        'runtime_minutes' => null,
        'overview' => '1986 senesinde Sovyet nükleer santralinde meydana gelen patlama sonrası santral işçileri ve itfaiyeciler faciayı kontrol altına almaya çalışır.',
        'cast' => [
            0 => [
                'name' => 'Jared Harris',
                'role' => 'Valery Legasov',
            ],
            1 => [
                'name' => 'Stellan Skarsgård',
                'role' => 'Boris Shcherbina',
            ],
            2 => [
                'name' => 'Emily Watson',
                'role' => 'Ulana Khomyuk',
            ],
            3 => [
                'name' => 'Paul Ritter',
                'role' => 'Anatoly Dyatlov',
            ],
        ],
        'watched_on' => '2026-09-07',
        'place' => 'evde',
        'rating' => 9.5,
        'is_favorite' => true,
        'is_rewatch' => false,
        'series_status' => 'finished',
        'current_season' => 1,
        'current_episode' => 5,
        'seasons' => [
            0 => [
                'number' => 1,
                'episode_count' => 5,
                'rating' => 9.5,
                'note' => 'Beş bölüm, sıfır dolgu.',
            ],
        ],
        'poster_file' => 'chernobyl.webp',
        'poster_colors' => [
            0 => '#acc4b4',
            1 => '#354a3c',
        ],
        'accent' => '#9eada3',
        'review' => 'Beş bölümlük bir felaket hikâyesi, ama asıl konusu radyasyon değil, yalan. Her bölümde biraz daha boğulduğumu hissettim; özellikle de söylenmesi gerekeni söylemekle bir sonraki tayini korumak arasında kalan memurları izlerken. Devlet dairesinde yedi yıl geçirmiş biri olarak bazı toplantı sahneleri fazla tanıdık geldi.

:::replik Valeri Legasov
Yalanların bedeli nedir?
:::',
    ],
    4 => [
        'type' => 'film',
        'slug' => 'past-lives',
        'title' => 'Başka Bir Hayatta',
        'original_title' => 'Past Lives',
        'year' => 2023,
        'creator' => 'Celine Song',
        'genres' => [
            0 => 'Dram',
            1 => 'Romantik',
        ],
        'runtime_minutes' => 106,
        'overview' => 'Birbirine derinden bağlı iki çocukluk arkadaşı olan Nora ve Hae Sung, Nora\'nın ailesi Güney Kore\'den göç edince ayrılmak zorunda kalır. Yirmi yıl sonra, kader ve aşk kavramlarıyla ve hayatı oluşturan seçimlerle yüzleşirken, önemli bir hafta boyunca New York\'ta yeniden bir araya gelirler.',
        'cast' => [
            0 => [
                'name' => 'Greta Lee',
                'role' => 'Nora',
            ],
            1 => [
                'name' => 'Teo Yoo',
                'role' => 'Hae Sung',
            ],
            2 => [
                'name' => 'John Magaro',
                'role' => 'Arthur',
            ],
            3 => [
                'name' => 'Moon Seung-ah',
                'role' => 'Young Nora',
            ],
        ],
        'watched_on' => '2026-09-03',
        'place' => 'uçakta',
        'rating' => 8.0,
        'is_favorite' => false,
        'is_rewatch' => false,
        'series_status' => null,
        'current_season' => null,
        'current_episode' => null,
        'seasons' => [
        ],
        'poster_file' => 'past-lives.webp',
        'poster_colors' => [
            0 => '#adb8c2',
            1 => '#364049',
        ],
        'accent' => '#3c4044',
        'review' => null,
    ],
    5 => [
        'type' => 'film',
        'slug' => 'anatomy-of-a-fall',
        'title' => 'Bir Düşüşün Anatomisi',
        'original_title' => 'Anatomie d\'une chute',
        'year' => 2023,
        'creator' => 'Justine Triet',
        'genres' => [
            0 => 'Gerilim',
            1 => 'Gizem',
            2 => 'Suç',
        ],
        'runtime_minutes' => 151,
        'overview' => 'Dünya prömiyerini yaptığı Cannes Film Festivali\'nde Altın Palmiye\'nin sahibi olan bu "Hitchcockvari mahkeme filmi" bir evliliğin dinamiklerini mercek altına yatıran bir psikolojik gerilim. "Birinin özel hayatı başkasının cehennemidir" fikrinden yola çıkan Bir Düşüşün Anatomisi, Fransız Alpleri\'nde bir kulübede kocası Samuel ve görme engelli oğluyla izole bir yaşam süren Alman yazar Sandra\'yı izliyor. Samuel yüksekten düşerek ölür fakat soruşturma sonucunda ölüm nedeninin intihar mı kaza mı olduğu kesinleşmeyince Sandra cinayet suçlamasıyla tutuklanır. Samuel\'in ölümünün sorgulandığı mahkeme süreci, çiftin çalkantılı ilişkilerinin de derinine inen rahatsız edici ve tatsız bir psikolojik yolculuğa dönüşür.',
        'cast' => [
            0 => [
                'name' => 'Sandra Hüller',
                'role' => 'Sandra Voyter',
            ],
            1 => [
                'name' => 'Swann Arlaud',
                'role' => 'Maître Vincent Renzi',
            ],
            2 => [
                'name' => 'Milo Machado-Graner',
                'role' => 'Daniel',
            ],
            3 => [
                'name' => 'Antoine Reinartz',
                'role' => 'Advocate General',
            ],
        ],
        'watched_on' => '2026-08-29',
        'place' => 'sinemada, yazlık gösterim',
        'rating' => 8.0,
        'is_favorite' => false,
        'is_rewatch' => false,
        'series_status' => null,
        'current_season' => null,
        'current_episode' => null,
        'seasons' => [
        ],
        'poster_file' => 'anatomy-of-a-fall.webp',
        'poster_colors' => [
            0 => '#a5adcb',
            1 => '#2f3651',
        ],
        'accent' => '#5e698f',
        'review' => 'Bir mahkeme filmi olarak başlıyor, bir evlilik filmine dönüşüyor. Sandra\'nın suçlu olup olmadığını film hiçbir zaman tam olarak söylemiyor ve bence en güçlü yanı da bu. Ses kaydı üzerinden dinlediğimiz kavga, son yıllarda izlediğim en gerçekçi tartışma.',
    ],
    6 => [
        'type' => 'film',
        'slug' => 'the-zone-of-interest',
        'title' => 'İlgi Alanı',
        'original_title' => 'The Zone of Interest',
        'year' => 2023,
        'creator' => 'Jonathan Glazer',
        'genres' => [
            0 => 'Dram',
            1 => 'Tarih',
            2 => 'Savaş',
        ],
        'runtime_minutes' => 105,
        'overview' => 'Auschwitz kumandanı Rudolf Höss, eşi Hedwig, çocukları ve hizmetkârlarıyla rüya gibi bir hayat sürmektedir. Öyle ki, ölüm kampının duvarına bakan muhteşem evleri tam da tren raylarıyla gaz odaları arasındadır. Martin Amis\'in aynı adlı romanından uyarlanan bu çarpıcı film çiçekli, geniş bahçeleri, seraları ve havuzlarında keyif süren Höss ailesinin başlarına ölüm külleri serpilirken süregiden sıradan gündelik yaşamını gözlemliyor.',
        'cast' => [
            0 => [
                'name' => 'Christian Friedel',
                'role' => 'Rudolf Höss',
            ],
            1 => [
                'name' => 'Sandra Hüller',
                'role' => 'Hedwig Höss',
            ],
            2 => [
                'name' => 'Johann Karthaus',
                'role' => 'Claus Höss',
            ],
            3 => [
                'name' => 'Luis Noah Witte',
                'role' => 'Hans Höss',
            ],
        ],
        'watched_on' => '2026-08-22',
        'place' => 'evde',
        'rating' => 7.0,
        'is_favorite' => false,
        'is_rewatch' => false,
        'series_status' => null,
        'current_season' => null,
        'current_episode' => null,
        'seasons' => [
        ],
        'poster_file' => 'the-zone-of-interest.webp',
        'poster_colors' => [
            0 => '#9cd4a2',
            1 => '#27592d',
        ],
        'accent' => '#2c5f32',
        'review' => null,
    ],
    7 => [
        'type' => 'series',
        'slug' => 'lost',
        'title' => 'Lost',
        'original_title' => null,
        'year' => 2004,
        'creator' => 'J.J. Abrams',
        'genres' => [
            0 => 'Gizem',
            1 => 'Aksiyon & Macera',
            2 => 'Dram',
        ],
        'runtime_minutes' => null,
        'overview' => 'Oceanic Havayolları\'nın Sidney-Los Angeles seferini yapan 815 sefer sayılı uçağı, okyanus üzerinden geçerken, manyetik bir alana kapılarak büyük bir adaya düşer. Fakat önceleri sıradan, tropik bir ada gibi görünen bu kara parçasının, kazazedelerin her birinin hayatını farklı biçimde değiştireceğinden habersizdirler.',
        'cast' => [
            0 => [
                'name' => 'Matthew Fox',
                'role' => 'Jack Shephard',
            ],
            1 => [
                'name' => 'Evangeline Lilly',
                'role' => 'Kate Austen',
            ],
            2 => [
                'name' => 'Terry O\'Quinn',
                'role' => 'John Locke',
            ],
            3 => [
                'name' => 'Josh Holloway',
                'role' => 'James Sawyer',
            ],
        ],
        'watched_on' => '2026-08-15',
        'place' => 'evde',
        'rating' => 6.0,
        'is_favorite' => false,
        'is_rewatch' => false,
        'series_status' => 'dropped',
        'current_season' => 3,
        'current_episode' => 7,
        'seasons' => [
            0 => [
                'number' => 1,
                'episode_count' => 25,
                'rating' => 8.5,
                'note' => 'Adanın gizemi en taze hâliyle.',
            ],
            1 => [
                'number' => 2,
                'episode_count' => 24,
                'rating' => 7.5,
                'note' => null,
            ],
            2 => [
                'number' => 3,
                'episode_count' => 23,
                'rating' => null,
                'note' => '7. bölümde bıraktım; cevap yerine yeni soru gelmeye devam etti.',
            ],
        ],
        'poster_file' => 'lost.webp',
        'poster_colors' => [
            0 => '#9fd1d1',
            1 => '#295656',
        ],
        'accent' => '#213f3f',
        'review' => null,
    ],
    8 => [
        'type' => 'film',
        'slug' => 'kis-uykusu',
        'title' => 'Kış Uykusu',
        'original_title' => null,
        'year' => 2014,
        'creator' => 'Nuri Bilge Ceylan',
        'genres' => [
            0 => 'Dram',
        ],
        'runtime_minutes' => 196,
        'overview' => 'Aydın emekli bir tiyatrocudur; oyunculuğu bıraktıktan sonra Kapadokya\'ya babasından yadigar kalan butik oteli işletmek için geri döner. Aydın o günden sonra başlayan kış uykusu bu gözlerden ırak otelin içerisindeki gündelikleriyle, kâh yerel bir gazeteye köşe yazıları yazarak kâh her zaman niyetlendiği ancak bir türlü başlayamadığı tiyatro tarihi kitabını yazmayı düşünerek geçer. Tüm bu süreçte hayatında iki kadın vardır: Kendisine her anlamda uzak ve soğuk davranan genç karısı Nihal ve boşandıktan sonra yanlarına taşınan kız kardeşi Necla... Kışın bastırması ve artan kar yağışı bu küçük taşrada en çok Aydın\'ın sinirlerine dokunur ve onu uzaklara gitmeye teşvik eder...',
        'cast' => [
            0 => [
                'name' => 'Haluk Bilginer',
                'role' => 'Aydın',
            ],
            1 => [
                'name' => 'Melisa Sözen',
                'role' => 'Nihal',
            ],
            2 => [
                'name' => 'Demet Akbağ',
                'role' => 'Necla',
            ],
            3 => [
                'name' => 'Ayberk Pekcan',
                'role' => 'Hidayet',
            ],
        ],
        'watched_on' => '2026-08-09',
        'place' => 'evde',
        'rating' => 9.0,
        'is_favorite' => true,
        'is_rewatch' => true,
        'series_status' => null,
        'current_season' => null,
        'current_episode' => null,
        'seasons' => [
        ],
        'poster_file' => 'kis-uykusu.webp',
        'poster_colors' => [
            0 => '#cab4a5',
            1 => '#503c2f',
        ],
        'accent' => '#cbbcb2',
        'review' => null,
    ],
    9 => [
        'type' => 'film',
        'slug' => 'oppenheimer',
        'title' => 'Oppenheimer',
        'original_title' => null,
        'year' => 2023,
        'creator' => 'Christopher Nolan',
        'genres' => [
            0 => 'Dram',
            1 => 'Tarih',
        ],
        'runtime_minutes' => 181,
        'overview' => 'İkinci Dünya Savaşı sırasında atom bombasının geliştirilmesine liderlik eden Amerikalı fizikçi J. Robert Oppenheimer\'ın hikayesi. Manhattan Projesi\'nin başına geçen Oppenheimer, insanlık tarihini değiştirecek bu ölümcül icadı hayata geçirirken, yarattığı gücün doğuracağı vicdani ve siyasi sonuçlarla da yüzleşmek zorunda kalır.',
        'cast' => [
            0 => [
                'name' => 'Cillian Murphy',
                'role' => 'J. Robert Oppenheimer',
            ],
            1 => [
                'name' => 'Emily Blunt',
                'role' => 'Kitty Oppenheimer',
            ],
            2 => [
                'name' => 'Matt Damon',
                'role' => 'Leslie Groves',
            ],
            3 => [
                'name' => 'Robert Downey Jr.',
                'role' => 'Lewis Strauss',
            ],
        ],
        'watched_on' => '2026-08-04',
        'place' => 'evde',
        'rating' => 7.5,
        'is_favorite' => false,
        'is_rewatch' => false,
        'series_status' => null,
        'current_season' => null,
        'current_episode' => null,
        'seasons' => [
        ],
        'poster_file' => 'oppenheimer.webp',
        'poster_colors' => [
            0 => '#e79f88',
            1 => '#6a2a15',
        ],
        'accent' => '#5c2210',
        'review' => null,
    ],
    10 => [
        'type' => 'series',
        'slug' => 'bir-baskadir',
        'title' => 'Bir Başkadır',
        'original_title' => null,
        'year' => 2020,
        'creator' => 'Berkun Oya',
        'genres' => [
            0 => 'Dram',
            1 => 'Gizem',
        ],
        'runtime_minutes' => null,
        'overview' => 'Dizi, birbirlerinden oldukça farklı karakterde olan ve bambaşka hayatlar yaşayan bir grup insanın yollarının kesişmesiyle değişen yaşamlarını konu ediyor. Bu karakterler ya yeni bir yola yürümek ya da karmaşık bir geçmişle hesaplaşmak zorunda kalacaklardır.',
        'cast' => [
            0 => [
                'name' => 'Öykü Karayel',
                'role' => 'Meryem',
            ],
            1 => [
                'name' => 'Funda Eryiğit',
                'role' => 'Ruhiye',
            ],
            2 => [
                'name' => 'Fatih Artman',
                'role' => 'Yasin',
            ],
            3 => [
                'name' => 'Defne Kayalar',
                'role' => 'Dr. Peri Aksoy',
            ],
        ],
        'watched_on' => '2026-08-01',
        'place' => 'evde',
        'rating' => 9.0,
        'is_favorite' => true,
        'is_rewatch' => true,
        'series_status' => 'finished',
        'current_season' => 1,
        'current_episode' => 8,
        'seasons' => [
            0 => [
                'number' => 1,
                'episode_count' => 8,
                'rating' => 9.0,
                'note' => 'Her bölüm başka bir karakterin gözünden; ikinci izleyişte daha da iyi.',
            ],
        ],
        'poster_file' => 'bir-baskadir.webp',
        'poster_colors' => [
            0 => '#dbb894',
            1 => '#5f4020',
        ],
        'accent' => '#cc9b69',
        'review' => null,
    ],
    11 => [
        'type' => 'series',
        'slug' => 'severance',
        'title' => 'Severance',
        'original_title' => null,
        'year' => 2022,
        'creator' => 'Dan Erickson',
        'genres' => [
            0 => 'Dram',
            1 => 'Gizem',
            2 => 'Bilim Kurgu & Fantazi',
        ],
        'runtime_minutes' => null,
        'overview' => 'Mark, anıları cerrahi müdahaleyle iş ve özel hayat olarak ayrılmış bir ofis çalışanı ekibinin başındadır. Gizemli bir iş arkadaşı ona iş yeri dışında göründüğünde, yaptıkları iş hakkındaki gerçeği keşfetmek için bir yolculuk başlar.',
        'cast' => [
            0 => [
                'name' => 'Adam Scott',
                'role' => 'Mark Scout',
            ],
            1 => [
                'name' => 'Britt Lower',
                'role' => 'Helly Riggs',
            ],
            2 => [
                'name' => 'Tramell Tillman',
                'role' => 'Seth Milchick',
            ],
            3 => [
                'name' => 'Zach Cherry',
                'role' => 'Dylan George',
            ],
        ],
        'watched_on' => '2026-09-26',
        'place' => 'evde',
        'rating' => null,
        'is_favorite' => false,
        'is_rewatch' => false,
        'series_status' => 'watching',
        'current_season' => 2,
        'current_episode' => 5,
        'seasons' => [
            0 => [
                'number' => 1,
                'episode_count' => 9,
                'rating' => 9.0,
                'note' => 'Son bölüm yılın en gergin kırk dakikasıydı.',
            ],
            1 => [
                'number' => 2,
                'episode_count' => 10,
                'rating' => null,
                'note' => null,
            ],
        ],
        'poster_file' => 'severance.webp',
        'poster_colors' => [
            0 => '#93c4dc',
            1 => '#1f4b60',
        ],
        'accent' => '#9fcbe0',
        'review' => null,
    ],
    12 => [
        'type' => 'series',
        'slug' => 'the-bear',
        'title' => 'The Bear',
        'original_title' => null,
        'year' => 2022,
        'creator' => 'Christopher Storer',
        'genres' => [
            0 => 'Dram',
            1 => 'Komedi',
        ],
        'runtime_minutes' => null,
        'overview' => 'Gastronomi dünyasından genç bir şef olan Carmen "Carmy" Berzatto, yürek burkan bir ölümün ardından ailesinin sandviç dükkanını işletmek için Chicago\'ya dönmek zorunda kalır. Kendi dünyasından uzakta olan Carmy, bir trajedinin sonuçlarına katlanırken küçük bir işletmenin, inatçı bir personelin ve gergin aile ilişkilerinin ezici sorumluluklarıyla uğraşmak zorundadır.',
        'cast' => [
            0 => [
                'name' => 'Jeremy Allen White',
                'role' => 'Carmen \'Carmy\' Berzatto',
            ],
            1 => [
                'name' => 'Ebon Moss-Bachrach',
                'role' => 'Richard \'Richie\' Jerimovich',
            ],
            2 => [
                'name' => 'Ayo Edebiri',
                'role' => 'Sydney Adamu',
            ],
            3 => [
                'name' => 'Lionel Boyce',
                'role' => 'Marcus Brooks',
            ],
        ],
        'watched_on' => '2026-09-24',
        'place' => 'evde',
        'rating' => null,
        'is_favorite' => false,
        'is_rewatch' => false,
        'series_status' => 'watching',
        'current_season' => 3,
        'current_episode' => 2,
        'seasons' => [
            0 => [
                'number' => 1,
                'episode_count' => 8,
                'rating' => 9.0,
                'note' => null,
            ],
            1 => [
                'number' => 2,
                'episode_count' => 10,
                'rating' => 9.5,
                'note' => '"Fishes" bölümü tek başına bir film gibi.',
            ],
            2 => [
                'number' => 3,
                'episode_count' => 10,
                'rating' => null,
                'note' => null,
            ],
        ],
        'poster_file' => 'the-bear.webp',
        'poster_colors' => [
            0 => '#8ba4e4',
            1 => '#182e67',
        ],
        'accent' => '#122556',
        'review' => null,
    ],
    13 => [
        'type' => 'series',
        'slug' => 'shogun',
        'title' => 'Shōgun',
        'original_title' => null,
        'year' => 2024,
        'creator' => 'Rachel Kondo',
        'genres' => [
            0 => 'Dram',
            1 => 'Savaş & Politik',
        ],
        'runtime_minutes' => null,
        'overview' => 'Japonya\'da 1600 yılında geçen hikayede Lord Yoshii Toranaga, yakınlardaki bir balıkçı köyünde gizemli bir Avrupa gemisi karaya oturduğunda, Naipler Konseyi\'ndeki düşmanları ona karşı birleştiği için hayatı uğruna savaşmaktadır.',
        'cast' => [
            0 => [
                'name' => 'Hiroyuki Sanada',
                'role' => 'Yoshii Toranaga',
            ],
            1 => [
                'name' => 'Cosmo Jarvis',
                'role' => 'John Blackthorne',
            ],
            2 => [
                'name' => 'Anna Sawai',
                'role' => 'Toda Mariko',
            ],
            3 => [
                'name' => 'Tadanobu Asano',
                'role' => 'Kashigi Yabushige',
            ],
        ],
        'watched_on' => '2026-07-18',
        'place' => 'evde',
        'rating' => null,
        'is_favorite' => false,
        'is_rewatch' => false,
        'series_status' => 'paused',
        'current_season' => 1,
        'current_episode' => 4,
        'seasons' => [
            0 => [
                'number' => 1,
                'episode_count' => 10,
                'rating' => null,
                'note' => 'Çok iyi gidiyordu, kafam başka yerdeyken izlemek istemedim. Kışın devam.',
            ],
        ],
        'poster_file' => 'shogun.webp',
        'poster_colors' => [
            0 => '#97d7d8',
            1 => '#235c5d',
        ],
        'accent' => '#225758',
        'review' => null,
    ],
];

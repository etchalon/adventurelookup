<?php

namespace Application\Migrations;

use AppBundle\Entity\Adventure;
use AppBundle\Entity\Author;
use AppBundle\Entity\Edition;
use AppBundle\Entity\Environment;
use AppBundle\Entity\Item;
use AppBundle\Entity\Monster;
use AppBundle\Entity\Publisher;
use AppBundle\Entity\Setting;
use AppBundle\Entity\TagContent;
use AppBundle\Entity\TagName;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Migrates data from the old dynamic data schema into our new static schema.
 * TODO: Delete the migration once executed on the live site. It will break in the future otherwise,
 *       because it assumes the existence of several classes / entities and methods which may
 *       not be present forever.
 */
class Version20170722192838 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const TAG_ID_AUTHORS = 73; 
    const TAG_ID_EDITION = 74;
    const TAG_ID_PUBLISHER = 75;
    const TAG_ID_SETTING = 76;
    const TAG_ID_MIN_LEVEL = 77;
    const TAG_ID_MAX_LEVEL = 78;
    const TAG_ID_LEVEL_RANGE = 79;
    const TAG_ID_SOLOABLE = 80;
    const TAG_ID_NUM_PAGES = 81;
    const TAG_ID_CHARACTERS = 82;
    const TAG_ID_ENVIRONMENTS = 83;
    const TAG_ID_LINK = 84;
    const TAG_ID_THUMB = 85;
    const TAG_ID_DESCRIPTION = 86;
    const TAG_ID_ITEMS = 87; 
    const TAG_ID_MONSTERS = 88;
    const TAG_ID_MAPS = 89;
    const TAG_ID_HANDOUTS = 90;
    const TAG_ID_VILLAINS = 91; 
    const TAG_ID_FOUND_IN = 92;
    const TAG_ID_PART_OF = 93; // TODO: This doesn't yet exist in our data model!!

    const IGNORED_TAG_CONTENT_IDS = [
        2738, // Says no maps included, but there are. Is also a duplicate of the correct 1036 tag
        675, // Has two settings, 'Setting Neutral' and 'Mystara'. This removes the 'Setting Neutral' tag
        1753, // Same as above
        765, // Has two settings, 'Setting Neutral' and 'Greyhawk'. This removes the 'Setting Neutral' tag
        974, // Same as above
        1085, // Same as above
        1111, // Same as above
        1637, // Same as above
        1674, // Same as above
        1717, // Same as above
        1892, // Same as above
        3518, // Same as above
        1946, // Has two settings, 'Setting Neutral' and 'Points of Light'. This removes the 'Setting Neutral' tag
        2142, // Has two settings, 'Spelljammer' and 'Forgotten Realms'. This removes the 'Forgotten Realms' tag
        812, // 'Sasquatch Game Studio' as publisher, only used once and same adventure is also tagged WotC
        2285, // 'B/X' and 'Labyrinth Lord' edition, removes 'B/X', as description says 'Labyrinth Lord'
        2337, // same as above
        2516, // same as above,
        3111, // Waterdeep is no setting but part of Forgotten Realms
        3112, // Urban is no setting
        3110, // Sword Coast is no setting
        3164, // Has both AD&D and AD&D, DmsGuild lists it as AD&D2
    ];
    
    private function getContentsForTagNameId(Collection $tagContents, int $tagNameId)
    {
        return array_unique(
            $tagContents
                ->filter(function (TagContent $tagContent) use ($tagNameId) { return $tagContent->getTag()->getId() == $tagNameId; })
                ->filter(function (TagContent $tagContent) { return !in_array($tagContent->getId(), self::IGNORED_TAG_CONTENT_IDS); })
                ->map(function (TagContent $tagContent) { return $tagContent->getContent(); })
                ->map(function ($content) { return ucfirst($content); })
                ->getValues(),
            SORT_REGULAR
        );
    }

    private function getContentsForTagNameIdAndAdventure(Adventure $adventure, int $tagNameId)
    {
        return array_unique(
            $adventure->getInfo()
                ->filter(function (TagContent $tagContent) use ($tagNameId) { return $tagContent->getTag()->getId() == $tagNameId; })
                ->filter(function (TagContent $tagContent) { return !in_array($tagContent->getId(), self::IGNORED_TAG_CONTENT_IDS); })
                ->map(function (TagContent $tagContent) { return $tagContent->getContent(); })
                ->getValues(),
            SORT_REGULAR
        );
    }
    
    private function convertBooleanField($value)
    {
        switch ($value) {
            case '':
                return null;
            case '0':
            case 'No':
            case 'false':
                return false;
            case '1':
            case 'true':
            case 'Only included in the Starter Set': // TODO: This is part of Adventure 17
                return true;
            default:
                $this->abortIf(true, 'Unknown boolean value encountered.');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $em->getConnection()->beginTransaction();

        $adventureRepository = $em->getRepository(Adventure::class);
        $tagNameRepository = $em->getRepository(TagName::class);
        $tagContentRepository = $em->getRepository(TagContent::class);

        $tagNames = $tagNameRepository->findAll();
        $this->abortIf(count($tagNames) != 21, 'Number of tag names changed!');
        $this->abortIf($tagNames[0]->getId() != 73);
        $this->abortIf($tagNames[20]->getId() != 93);

        $contentsToUpdate = [
            [
                'tag' => self::TAG_ID_MONSTERS,
                'old' => 'dire wolf',
                'new' => 'Dire Wolf'
            ],
            [
                'tag' => self::TAG_ID_MONSTERS,
                'old' => 'skeleton',
                'new' => 'Skeleton'
            ],
            [
                'tag' => self::TAG_ID_ITEMS,
                'old' => 'bag of devouring',
                'new' => 'Bag of Devouring'
            ],
            [
                'tag' => self::TAG_ID_ITEMS,
                'old' => 'javelin of lightning',
                'new' => 'Javelin of Lightning'
            ],
            [
                'tag' => self::TAG_ID_ITEMS,
                'old' => 'wand of wonder',
                'new' => 'Want of Wonder',
            ],
            [
                'tag' => self::TAG_ID_ITEMS,
                'old' => 'ring of one wish',
                'new' => 'Ring of One Wish'
            ],
            [
                'tag' => self::TAG_ID_ITEMS,
                'old' => 'mace of disruption',
                'new' => 'Mace of Disruption'
            ],
            [
                'tag' => self::TAG_ID_ITEMS,
                'old' => 'stone of weight',
                'new' => 'Stone of Weight'
            ],
            [
                'tag' => self::TAG_ID_ITEMS,
                'old' => 'amulet of life protection',
                'new' => 'Amulet of Life Protection'
            ],
            [
                'tag' => self::TAG_ID_PUBLISHER,
                'old' => 'WotC',
                'new' => 'Wizards of the Coast'
            ],
            [
                'tag' => self::TAG_ID_EDITION,
                'old' => '5e',
                'new' => '5th Edition'
            ]
        ];
        foreach ($contentsToUpdate as $contentToUpdate) {
            $qb = $tagContentRepository->createQueryBuilder('c');
            $qb
                ->update(TagContent::class, 'c')
                ->set('c.content', ':new_value')
                ->where($qb->expr()->andX(
                    $qb->expr()->eq('c.tag', ':tag_id'),
                    $qb->expr()->eq('c.content', ':old_value')
                ))
                ->setParameter('tag_id', $contentToUpdate['tag'])
                ->setParameter('old_value', $contentToUpdate['old'])
                ->setParameter('new_value', $contentToUpdate['new'])
                ->getQuery()
                ->execute();
        }

        $tagContents = new ArrayCollection($tagContentRepository->findAll());
        $authorNames = $this->getContentsForTagNameId($tagContents, self::TAG_ID_AUTHORS);
        $environmentNames = $this->getContentsForTagNameId($tagContents, self::TAG_ID_ENVIRONMENTS);
        $itemNames = $this->getContentsForTagNameId($tagContents, self::TAG_ID_ITEMS);
        $monsterNames = $this->getContentsForTagNameId($tagContents, self::TAG_ID_MONSTERS);
        $villainNames = $this->getContentsForTagNameId($tagContents, self::TAG_ID_VILLAINS);
        foreach ($villainNames as $villainName) {
            if (($key = array_search($villainName, $monsterNames)) !== false) {
                unset($monsterNames[$key]);
            }
        }

        $authors = [];
        foreach ($authorNames as $authorName) {
            $author = new Author();
            $author->setName($authorName);
            $em->persist($author);
            $authors[] = $author;
        }
        $environments = [];
        foreach ($environmentNames as $environmentName) {
            $environment = new Environment();
            $environment->setName($environmentName);
            $em->persist($environment);
            $environments[] = $environment;
        }
        $items = [];
        foreach ($itemNames as $itemName) {
            $item = new Item();
            $item->setName($itemName);
            $em->persist($item);
            $items[] = $item;
        }
        $monsters = [];
        foreach ($monsterNames as $monsterName) {
            $monster = new Monster();
            $monster
                ->setName($monsterName)
                ->setIsUnique(false);
            $em->persist($monster);
            $monsters[] = $monster;
        }
        foreach ($villainNames as $villainName) {
            $villain = new Monster();
            $villain
                ->setName($villainName)
                ->setIsUnique(true);
            $em->persist($villain);
            $monsters[] = $villain;
        }
        $em->flush();

        $editionNames = $this->getContentsForTagNameId($tagContents, self::TAG_ID_EDITION);
        $publisherNames = $this->getContentsForTagNameId($tagContents, self::TAG_ID_PUBLISHER);
        $settingNames = $this->getContentsForTagNameId($tagContents, self::TAG_ID_SETTING);
        
        $editions = [];
        $i = 10;
        foreach ($editionNames as $editionName) {
            $edition = new Edition();
            $edition
                ->setName($editionName)
                ->setPosition($i);
            $em->persist($edition);
            $editions[] = $edition;
            $i += 10;
        }
        $publishers = [];
        foreach ($publisherNames as $publisherName) {
            $publisher = new Publisher();
            $publisher->setName($publisherName);
            $em->persist($publisher);
            $publishers[] = $publisher;
        }
        $settings = [];
        foreach ($settingNames as $settingName) {
            $setting = new Setting();
            $setting->setName($settingName);
            $em->persist($setting);
            $settings[] = $setting;
        }

        $adventures = $adventureRepository->findAll();
        foreach ($adventures as $adventure) {
            if (in_array($adventure->getId(), [
                102, // TODO: This adventure is for two different editions
                17, // TODO: This adventure has multiple links
            ])) {
                continue;
            }

            $minStartingLevel = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_MIN_LEVEL);
            $maxStartingLevel = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_MAX_LEVEL);
            $startingLevelRange = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_LEVEL_RANGE);

            $links = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_LINK);
            $thumbnails = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_THUMB);
            $foundIns = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_FOUND_IN);
            $descriptions = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_DESCRIPTION);

            $numPages = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_NUM_PAGES);
            $pregeneratedCharacters = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_CHARACTERS);
            $soloables = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_SOLOABLE);
            $tacticalMaps = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_MAPS);
            $handouts = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_HANDOUTS);

            $this->abortIf(count($minStartingLevel) > 1, sprintf('Adventure #%s "%s" has %s minStartingLevel: %s', $adventure->getId(), $adventure->getTitle(), count($minStartingLevel), implode(', ', $minStartingLevel)));
            if (count($minStartingLevel) == 1) {
                $adventure->setMinStartingLevel($minStartingLevel[0]);
            }

            $this->abortIf(count($maxStartingLevel) > 1, sprintf('Adventure #%s "%s" has %s maxStartingLevel: %s', $adventure->getId(), $adventure->getTitle(), count($maxStartingLevel), implode(', ', $maxStartingLevel)));
            if (count($maxStartingLevel) == 1) {
                $adventure->setMaxStartingLevel($maxStartingLevel[0]);
            }

            $this->warnIf(count($startingLevelRange) > 1, sprintf('Adventure #%s "%s" has %s startingLevelRange: %s', $adventure->getId(), $adventure->getTitle(), count($startingLevelRange), implode(', ', $startingLevelRange)));
            if (count($startingLevelRange) > 0) {
                $adventure->setStartingLevelRange(implode(', ', $startingLevelRange));
            }
            
            $this->abortIf(count($links) > 1, sprintf('Adventure #%s "%s" has %s links: %s', $adventure->getId(), $adventure->getTitle(), count($links), implode(', ', $links)));
            if (count($links) == 1) {
                $adventure->setLink($links[0]);
            }

            $this->abortIf(count($thumbnails) > 1, sprintf('Adventure #%s "%s" has %s thumbnails: %s', $adventure->getId(), $adventure->getTitle(), count($thumbnails), implode(', ', $thumbnails)));
            if (count($thumbnails) == 1) {
                $adventure->setThumbnailUrl($thumbnails[0]);
            }

            $this->warnIf(count($foundIns) > 1, sprintf('Adventure #%s "%s" has %s foundIns: %s', $adventure->getId(), $adventure->getTitle(), count($foundIns), implode(', ', $foundIns)));
            if (count($foundIns) > 0) {
                $adventure->setFoundIn(implode(', ', $foundIns));
            }

            $this->warnIf(count($descriptions) > 1, sprintf('Adventure #%s "%s" has %s descriptions: %s', $adventure->getId(), $adventure->getTitle(), count($descriptions), implode(', ', $descriptions)));
            if (count($descriptions) > 0) {
                $adventure->setDescription(implode("\n\n", $descriptions));
            }

            $this->abortIf(count($numPages) > 1, sprintf('Adventure #%s "%s" has %s numPages: %s', $adventure->getId(), $adventure->getTitle(), count($numPages), implode(', ', $numPages)));
            if (count($numPages) == 1) {
                $adventure->setNumPages($numPages[0]);
            }

            $this->abortIf(count($pregeneratedCharacters) > 1, sprintf('Adventure #%s "%s" has %s pregeneratedCharacters: %s', $adventure->getId(), $adventure->getTitle(), count($pregeneratedCharacters), implode(', ', $pregeneratedCharacters)));
            if (count($pregeneratedCharacters) == 1) {
                $adventure->setPregeneratedCharacters($this->convertBooleanField($pregeneratedCharacters[0]));
            }

            $this->abortIf(count($tacticalMaps) > 1, sprintf('Adventure #%s "%s" has %s tacticalMaps: %s', $adventure->getId(), $adventure->getTitle(), count($tacticalMaps), implode(', ', $tacticalMaps)));
            if (count($tacticalMaps) == 1) {
                $adventure->setTacticalMaps($this->convertBooleanField($tacticalMaps[0]));
            }

            $this->abortIf(count($handouts) > 1, sprintf('Adventure #%s "%s" has %s handouts: %s', $adventure->getId(), $adventure->getTitle(), count($handouts), implode(', ', $handouts)));
            if (count($handouts) == 1) {
                $adventure->setHandouts($this->convertBooleanField($handouts[0]));
            }

            $this->abortIf(count($soloables) > 1, sprintf('Adventure #%s "%s" has %s soloables: %s', $adventure->getId(), $adventure->getTitle(), count($soloables), implode(', ', $soloables)));
            if (count($soloables) == 1) {
                $adventure->setSoloable($this->convertBooleanField($soloables[0]));
            }

            
            $authorNames = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_AUTHORS);
            $environmentNames = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_ENVIRONMENTS);
            $itemNames = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_ITEMS);
            $monsterNames = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_MONSTERS);
            $villainNames = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_VILLAINS);
            foreach ($villainNames as $villainName) {
                if (($key = array_search($villainName, $monsterNames)) !== false) {
                    unset($monsterNames[$key]);
                }
            }

            foreach ($authorNames as $authorName) {
                foreach ($authors as $author) {
                    if ($author->getName() == $authorName) {
                        $adventure->addAuthor($author);
                        break;
                    }
                }
            }
            foreach ($environmentNames as $environmentName) {
                foreach ($environments as $environment) {
                    if ($environment->getName() == $environmentName) {
                        $adventure->addEnvironment($environment);
                        break;
                    }
                }
            }
            foreach ($itemNames as $itemName) {
                foreach ($items as $item) {
                    if ($item->getName() == $itemName) {
                        $adventure->addItem($item);
                        break;
                    }
                }
            }
            foreach ($monsterNames as $monsterName) {
                foreach ($monsters as $monster) {
                    if ($monster->getName() == $monsterName) {
                        $adventure->addMonster($monster);
                        break;
                    }
                }
            }
            foreach ($villainNames as $villainName) {
                foreach ($monsters as $monster) {
                    if ($monster->getName() == $villainName) {
                        $adventure->addMonster($monster);
                        break;
                    }
                }
            }

            
            $editionNames = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_EDITION);
            $publisherNames = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_PUBLISHER);
            $settingNames = $this->getContentsForTagNameIdAndAdventure($adventure, self::TAG_ID_SETTING);

            $this->abortIf(count($editionNames) > 1, sprintf('Adventure #%s "%s" has %s editions: %s', $adventure->getId(), $adventure->getTitle(), count($editionNames), implode(', ', $editionNames)));
            if (count($editionNames) == 1) {
                foreach ($editions as $edition) {
                    if ($edition->getName() == $editionNames[0]) {
                        $adventure->setEdition($edition);
                        break;
                    }
                }
            }
            $this->abortIf(count($publisherNames) > 1, sprintf('Adventure #%s "%s" has %s publishers: %s', $adventure->getId(), $adventure->getTitle(), count($publisherNames), implode(', ', $publisherNames)));
            if (count($publisherNames) == 1) {
                foreach ($publishers as $publisher) {
                    if ($publisher->getName() == $publisherNames[0]) {
                        $adventure->setPublisher($publisher);
                        break;
                    }
                }
            }
            $this->abortIf(count($settingNames) > 1, sprintf('Adventure #%s "%s" has %s settings: %s', $adventure->getId(), $adventure->getTitle(), count($settingNames), implode(', ', $settingNames)));
            if (count($settingNames) == 1) {
                foreach ($settings as $setting) {
                    if ($setting->getName() == $settingNames[0]) {
                        $adventure->setSetting($setting);
                        break;
                    }
                }
            }
        }

        $em->flush();
        
        $em->getConnection()->commit();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->throwIrreversibleMigrationException();
    }
}

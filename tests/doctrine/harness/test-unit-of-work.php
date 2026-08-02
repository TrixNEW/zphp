<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

require __DIR__ . '/../app/vendor/autoload.php';

#[ORM\Entity]
class Author
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $name;

    /** @var Collection<int, Post> */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Post::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $posts;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->posts = new ArrayCollection();
    }

    public function addPost(Post $post): void
    {
        $this->posts->add($post);
    }

    public function id(): ?int { return $this->id; }
    public function name(): string { return $this->name; }
    public function rename(string $name): void { $this->name = $name; }
    public function postCount(): int { return $this->posts->count(); }
}

#[ORM\Entity]
class Post
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column] private string $title,
        #[ORM\ManyToOne(inversedBy: 'posts')] private Author $author,
        #[ORM\Column(nullable: true)] private ?string $subtitle = null,
    ) {
        $author->addPost($this);
    }

    public function title(): string { return $this->title; }
}

$config = ORMSetup::createAttributeMetadataConfiguration([__DIR__], true);
$em = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config), $config);
$tool = new SchemaTool($em);
$tool->createSchema([$em->getClassMetadata(Author::class), $em->getClassMetadata(Post::class)]);

$author = new Author('Ada');
$em->persist($author);
$em->persist(new Post('First', $author));
$em->persist(new Post('Second', $author, 'Optional'));
$em->flush();
$id = $author->id();
echo "created: $id posts=" . $author->postCount() . "\n";

$em->clear();
$first = $em->find(Author::class, $id);
$second = $em->find(Author::class, $id);
echo 'identity: ' . ($first === $second ? 'same' : 'different') . "\n";
echo 'loaded: ' . $first->name() . ' posts=' . $first->postCount() . "\n";
$first->rename('Augusta');
$em->flush();
$em->clear();
echo 'updated: ' . $em->find(Author::class, $id)->name() . "\n";

$titles = $em->createQuery('SELECT p FROM Post p ORDER BY p.id')->getResult();
echo 'titles: ' . implode(',', array_map(fn (Post $post) => $post->title(), $titles)) . "\n";

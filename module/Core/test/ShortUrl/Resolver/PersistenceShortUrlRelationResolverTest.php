<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\PersistenceShortUrlRelationResolver;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class PersistenceShortUrlRelationResolverTest extends TestCase
{
    use ProphecyTrait;

    private PersistenceShortUrlRelationResolver $resolver;
    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->resolver = new PersistenceShortUrlRelationResolver($this->em->reveal());
    }

    /** @test */
    public function returnsEmptyWhenNoDomainIsProvided(): void
    {
        $getRepository = $this->em->getRepository(Domain::class);

        self::assertNull($this->resolver->resolveDomain(null));
        $getRepository->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideFoundDomains
     */
    public function findsOrCreatesDomainWhenValueIsProvided(?Domain $foundDomain, string $authority): void
    {
        $repo = $this->prophesize(ObjectRepository::class);
        $findDomain = $repo->findOneBy(['authority' => $authority])->willReturn($foundDomain);
        $getRepository = $this->em->getRepository(Domain::class)->willReturn($repo->reveal());

        $result = $this->resolver->resolveDomain($authority);

        if ($foundDomain !== null) {
            self::assertSame($result, $foundDomain);
        }
        self::assertInstanceOf(Domain::class, $result);
        self::assertEquals($authority, $result->getAuthority());
        $findDomain->shouldHaveBeenCalledOnce();
        $getRepository->shouldHaveBeenCalledOnce();
    }

    public function provideFoundDomains(): iterable
    {
        $authority = 'doma.in';

        yield 'not found domain' => [null, $authority];
        yield 'found domain' => [new Domain($authority), $authority];
    }

    /** @test */
    public function returnsEmptyWhenNoApiKeyIsProvided(): void
    {
        $getRepository = $this->em->getRepository(ApiKey::class);

        self::assertNull($this->resolver->resolveApiKey(null));
        $getRepository->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideFoundApiKeys
     */
    public function triesToFindApiKeyWhenValueIsProvided(?ApiKey $foundApiKey, string $key): void
    {
        $repo = $this->prophesize(ObjectRepository::class);
        $find = $repo->findOneBy(['key' => $key])->willReturn($foundApiKey);
        $getRepository = $this->em->getRepository(ApiKey::class)->willReturn($repo->reveal());

        $result = $this->resolver->resolveApiKey($key);

        self::assertSame($result, $foundApiKey);
        $find->shouldHaveBeenCalledOnce();
        $getRepository->shouldHaveBeenCalledOnce();
    }

    public function provideFoundApiKeys(): iterable
    {
        $key = 'abc123';

        yield 'not found api key' => [null, $key];
        yield 'found api key' => [new ApiKey(), $key];
    }
}

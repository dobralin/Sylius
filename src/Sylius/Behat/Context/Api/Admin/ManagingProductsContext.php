<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Behat\Context\Api\Admin;

use Behat\Behat\Context\Context;
use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Formatter\StringInflector;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Product\Model\ProductOption;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Webmozart\Assert\Assert;

final class ManagingProductsContext implements Context
{
    /** @var ApiClientInterface */
    private $client;

    /** @var ApiClientInterface */
    private $productReviewClient;

    /** @var ResponseCheckerInterface */
    private $responseChecker;

    /** @var SharedStorageInterface */
    private $sharedStorage;

    public function __construct(
        ApiClientInterface $client,
        ApiClientInterface $productReviewClient,
        ResponseCheckerInterface $responseChecker,
        SharedStorageInterface $sharedStorage
    ) {
        $this->client = $client;
        $this->productReviewClient = $productReviewClient;
        $this->responseChecker = $responseChecker;
        $this->sharedStorage = $sharedStorage;
    }

    /**
     * @When I want to create a new configurable product
     * @When I want to create a new simple product
     */
    public function iWantToCreateANewConfigurableProduct(): void
    {
        $this->client->buildCreateRequest();
    }

    /**
     * @When I specify its code as :code
     * @When I do not specify its code
     */
    public function iSpecifyItsCodeAs(string $code = null): void
    {
        $this->client->addRequestData('code', $code);
    }

    /**
     * @When I name it :name in :localeCode
     * @When I rename it to :name in :localeCode
     */
    public function iRenameItToIn(string $name, string $localeCode): void
    {
        $data = [
            'translations' => [
                $localeCode => [
                    'locale' => $localeCode,
                    'name' => $name,
                    'slug' => StringInflector::nameToSlug($name)
                ],
            ],
        ];

        $this->client->updateRequestData($data);
    }

    /**
     * @When /^I set its(?:| default) price to "(?:€|£|\$)([^"]+)" for ("[^"]+" channel)$/
     */
    public function iSetItsPriceTo(string $price, ChannelInterface $channel): void
    {
        $localeCode = $channel->getLocales()->first()->getCode();

        $data = [
            'translations' => [
                $localeCode => [
                    'locale' => $localeCode,
                    'price' => $price,
                ],
            ],
            'channel' => '/new-api/channels/' . $channel->getCode()
        ];

        $this->client->updateRequestData($data);
    }

    /**
     * @When I set its slug to :slug
     * @When I set its slug to :slug in :localeCode
     * @When I remove its slug
     */
    public function iSetItsSlugTo(?string $slug = null, $localeCode = 'en_US'): void
    {
        $data = [
            'translations' => [
                $localeCode => [
                    'locale' => $localeCode,
                    'slug' => $slug,
                ],
            ],
        ];

        $this->client->updateRequestData($data);
    }

    /**
     * @When I add it
     * @When I try to add it
     */
    public function iAddIt(): void
    {
        $this->client->create();
    }

    /**
     * @When I add the :productOption option to it
     */
    public function iAddTheOptionToIt(ProductOption $productOption): void
    {
        $this
            ->client
            ->updateRequestData(['options' => ['/new-api/product_options/' . $productOption->getCode()]]);
    }

    /**
     * @When I save my changes
     * @When I try to save my changes
     */
    public function iSaveMyChanges(): void
    {
        $this->client->update();
    }

    /**
     * @When I filter them by :taxon taxon
     */
    public function iFilterThemByTaxon(TaxonInterface $taxon): void
    {
        $this->client->addFilter('productTaxons.taxon.code', $taxon->getCode());
        $this->client->filter();
    }

    /**
     * @Given I am browsing products
     * @When I browse products
     * @When I want to browse products
     */
    public function iWantToBrowseProducts(): void
    {
        $this->client->index();
    }

    /**
     * @When I delete the :product product
     * @When I try to delete the :product product
     */
    public function iDeleteProduct(ProductInterface $product): void
    {
        $this->client->delete($product->getCode());
    }

    /**
     * @When I want to modify the :product product
     * @When /^I want to modify (this product)$/
     * @When I modify the :product product
     */
    public function iWantToModifyAProduct(ProductInterface $product): void
    {
        $this->client->buildUpdateRequest($product->getCode());
    }

    /**
     * @When I enable slug modification
     * @When I enable slug modification in :localeCode
     */
    public function iEnableSlugModification(): void
    {
        //intentionally blank line
    }

    /**
     * @Then I should see the product :productName in the list
     * @Then the product :productName should appear in the store
     * @Then the product :productName should be in the shop
     * @Then this product should still be named :productName
     */
    public function theProductShouldAppearInTheShop(string $productName): void
    {
        $response = $this->client->index();

        Assert::true(
            $this
                ->responseChecker
                ->hasItemWithTranslation($response,'en_US', 'name', $productName)
        );
    }

    /**
     * @Then I should be notified that it has been successfully created
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyCreated(): void
    {
        Assert::true($this->responseChecker->isCreationSuccessful($this->client->getLastResponse()));
    }

    /**
     * @Then I should be notified that it has been successfully edited
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyEdited(): void
    {
        Assert::true(
            $this->responseChecker->isUpdateSuccessful($this->client->getLastResponse()),
            'Product option could not be edited'
        );
    }

    /**
     * @Then I should be notified that it has been successfully deleted
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyDeleted(): void
    {
        Assert::true($this->responseChecker->isDeletionSuccessful($this->client->getLastResponse()));
    }

    /**
     * @Then I should see :numberOfProducts products in the list
     */
    public function iShouldSeeProductsInTheList(int $count): void
    {
        Assert::count($this->responseChecker->getCollection($this->client->getLastResponse()), $count);
    }

    /**
     * @Then I should( still) see a product with :field :value
     */
    public function iShouldSeeProductWith(string $field, string $value): void
    {
        Assert::true(
            $this
                ->responseChecker
                ->hasItemWithTranslation($this->client->getLastResponse(), 'en_US', $field, $value)
        );
    }

    /**
     * @Then I should not see any product with :field :value
     */
    public function iShouldNotSeeAnyProductWith(string $field, string $value): void
    {
        $this->client->update();

        Assert::false(
            $this
                ->responseChecker
                ->hasItemWithTranslation($this->client->getLastResponse(), 'en_US', $field, $value)
        );
    }

    /**
     * @Then I should not be able to edit its code
     */
    public function iShouldNotBeAbleToEditItsCode(): void
    {
        $this->client->addRequestData('code', '_NEW');
        $response = $this->client->index();

        Assert::false(
            $this
                ->responseChecker
                ->hasItemOnPositionWithValue(
                    $response,
                    0,
                    'code',
                    '/new-api/products/_NEW',
                    ),
                sprintf('It was possible to change %s', '_NEW')
        );
    }

    /**
     * @Then /^(this product) name should be "([^"]+)"$/
     */
    public function thisProductNameShouldBe(ProductInterface $product, string $name): void
    {
        $response = $this->client->index();

        Assert::true(
            $this
                ->responseChecker
                ->hasItemWithTranslation($response, 'en_US', 'name', $name)
        );
    }

    /**
     * @Then /^(this product) should not exist in the product catalog$/
     */
    public function productShouldNotExist(ProductInterface $product): void
    {
        $response = $this->client->index();

        Assert::false(
            $this
                ->responseChecker
                ->hasItemWithValue($response, 'code', $product->getCode())
        );
    }

    /**
     * @Then /^this product should have (?:a|an) ("[^"]+" option)$/
     */
    public function thisProductShouldHaveOption(ProductOptionInterface $productOption): void
    {
        $response = $this->client->index();

        $options = [];
        $options[0] = sprintf('/new-api/product_options/%s', $productOption->getCode());

        Assert::true(
            $this
                ->responseChecker
                ->hasItemOnPositionWithValue(
                    $response,
                    1,
                    'options',
                    $options
                )
        );
    }

    /**
     * @Then /^the slug of the ("[^"]+" product) should(?:| still) be "([^"]+)"$/
     * @Then /^the slug of the ("[^"]+" product) should(?:| still) be "([^"]+)" (in the "[^"]+" locale)$/
     */
    public function productSlugShouldBe(ProductInterface $product, string $slug, $locale = 'en_US'): void
    {
        $response = $this->client->index();

        Assert::true(
            $this
                ->responseChecker
                ->hasItemWithTranslation($response, $locale, 'slug', $slug)
        );
    }

    /**
     * @Then /^there should be no reviews of (this product)$/
     */
    public function thereAreNoProductReviews(ProductInterface $product): void
    {
        $response = $this->productReviewClient->index();

        Assert::true(
            empty(
                $this
                    ->responseChecker
                    ->getCollectionItemsWithValue(
                        $response,
                        'reviewSubject',
                        '/new-api/products/' . $product->getCode()
                )
            )
        );
    }

}

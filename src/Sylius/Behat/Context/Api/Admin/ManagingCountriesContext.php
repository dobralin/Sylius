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

use ApiPlatform\Core\Api\IriConverterInterface;
use Behat\Behat\Context\Context;
use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Addressing\Model\ProvinceInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Serializer\SerializerInterface;
use Webmozart\Assert\Assert;

final class ManagingCountriesContext implements Context
{
    /** @var ApiClientInterface */
    private $client;

    /** @var ResponseCheckerInterface */
    private $responseChecker;

    /** @var SerializerInterface */
    private $serializer;

    /** @var SharedStorageInterface */
    private $sharedStorage;

    /** @var IriConverterInterface */
    private $iriConverter;

    public function __construct(
        ApiClientInterface $client,
        ResponseCheckerInterface $responseChecker,
        SerializerInterface $serializer,
        SharedStorageInterface $sharedStorage,
        IriConverterInterface $iriConverter
    ) {
        $this->client = $client;
        $this->responseChecker = $responseChecker;
        $this->serializer = $serializer;
        $this->sharedStorage = $sharedStorage;
        $this->iriConverter = $iriConverter;
    }

    /**
     * @When I want to add a new country
     */
    public function iWantToAddANewCountry(): void
    {
        $this->client->buildCreateRequest();
    }

    /**
     * @When I choose :countryName
     */
    public function iChoose(string $countryName): void
    {
        $this->client->addRequestData('code', $this->getCountryCodeByName($countryName));
    }

    /**
     * @When I add it
     */
    public function iAddIt(): void
    {
        $this->client->create();
    }

    /**
     * @Then I should be notified that it has been successfully created
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyCreated(): void
    {
        Assert::true(
            $this->responseChecker->isCreationSuccessful($this->client->getLastResponse()),
            'Country could not be created'
        );
    }

    /**
     * @Then the country :country should appear in the store
     */
    public function theCountryShouldAppearInTheStore(CountryInterface $country): void
    {
        Assert::true(
            $this->responseChecker->hasItemWithValue($this->client->index(), 'code', $country->getCode()),
            sprintf('There is no country with name "%s"', $country->getName())
        );
    }

    /**
     * @Then the country :country should have the :province province
     */
    public function theCountryShouldHaveTheProvince(CountryInterface $country, ProvinceInterface $province): void
    {
        Assert::true($this->responseChecker->hasItemWithValue(
            $this->client->subResourceIndex('provinces', $country->getCode()),
            'code',
            $province->getCode()
        ));
    }

    /**
     * @Then /^(this country) should have the "([^"]*)" province$/
     */
    public function thisCountryShouldHaveTheProvince(CountryInterface $country, $provinceName): void
    {
        Assert::true($this->responseChecker->hasItemWithValue(
            $this->client->subResourceIndex('provinces', $country->getCode()),
            'name',
            $provinceName
        ));
    }

    /**
     * @Then I should not be able to choose :countryName
     */
    public function iShouldNotBeAbleToChoose(string $countryName): void
    {
        $this->client->addRequestData('code', $this->getCountryCodeByName($countryName));
        $response = $this->client->create();
        Assert::false(
            $this->responseChecker->isCreationSuccessful($response),
            'Country has been created successfully, but it should not'
        );
        Assert::same($this->responseChecker->getError($response), 'code: Country ISO code must be unique.');
    }

    /**
     * @When /^I want to edit (this country)$/
     */
    public function iWantToEditThisCountry(CountryInterface $country): void
    {
        $this->sharedStorage->set('country', $country);
        $this->client->buildUpdateRequest($country->getCode());
    }

    /**
     * @When I enable it
     */
    public function iEnableIt(): void
    {
        $this->client->addRequestData('enabled', true);
    }

    /**
     * @When I disable it
     */
    public function iDisableIt(): void
    {
        $this->client->addRequestData('enabled', false);
    }

    /**
     * @When I save my changes
     * @When I try to save changes
     */
    public function iSaveMyChanges(): void
    {
        $this->client->update();
    }

    /**
     * @Then I should be notified that it has been successfully edited
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyEdited(): void
    {
        Assert::true(
            $this->responseChecker->isUpdateSuccessful($this->client->getLastResponse()),
            'Country could not be edited'
        );
    }

    /**
     * @Then /^(this country) should be ([^"]+)$/
     */
    public function thisCountryShouldBeDisabled(CountryInterface $country, string $enabled): void
    {
        Assert::true(
            $this->responseChecker->hasValue(
                $this->client->show($country->getCode()),
                'enabled',
                $enabled === 'enabled' ? true : false
            ),
            'Country is not disabled'
        );
    }

    /**
     * @Then the code field should be disabled
     */
    public function theCodeFieldShouldBeDisabled(): void
    {
        /** @var CountryInterface $country */
        $country = $this->sharedStorage->get('country');

        $countryUpdateSerialised = $this->serializer->serialize($country, 'json', ['groups' => 'country:update']);
        Assert::keyNotExists(\json_decode($countryUpdateSerialised, true), 'code');
    }

    /**
     * @When I add the :provinceName province with :provinceCode code
     */
    public function iAddTheProvinceWithCode(string $provinceName, string $provinceCode): void
    {
        $this->client->addSubResourceData(
            'provinces',
            ['code' => $provinceCode, 'name' => $provinceName]
        );
    }

    /**
     * @When I add the :provinceName province with :provinceCode code and :provinceAbbreviation abbreviation
     */
    public function iAddTheProvinceWithCodeAndAbbreviation(string $provinceName, string $provinceCode, string $provinceAbbreviation): void
    {
        $this->client->addSubResourceData(
            'provinces',
            ['code' => $provinceCode, 'name' => $provinceName, 'abbreviation' => $provinceAbbreviation]
        );
    }

    /**
     * @When I delete the :province province of this country
     */
    public function iDeleteTheProvinceOfThisCountry(ProvinceInterface $province): void
    {
        /** @var CountryInterface $country */
        $country = $this->sharedStorage->get('country');

        foreach ($country->getProvinces() as $key => $provinceValue) {
            if ($province->getId() === $provinceValue->getId()) {
                $this->client->removeSubResource('provinces', $key);
            }
        }
    }

    /**
     * @Then this country should not have the :provinceName province
     * @Then province with name :provinceName should not be added in this country
     */
    public function thisCountryShouldNotHaveTheProvince(string $provinceName): void
    {
        /** @var CountryInterface $country */
        $country = $this->sharedStorage->get('country');

        $response = $this->client->show($country->getCode());
        $countryFromResponse = $this->responseChecker->getResponseContent($response);

        foreach ($countryFromResponse['provinces'] as $provinceFromResponse)
        {
            /** @var ProvinceInterface $province */
            $province = $this->iriConverter->getItemFromIri($provinceFromResponse);
            Assert::false(
                $province->getName() === $provinceName,
                sprintf('The country "%s" should not have the "%s" province', $country->getName(), $province->getName())
            );
        }
    }

    /**
     * @Then I should be notified that province code must be unique
     */
    public function iShouldBeNotifiedThatProvinceCodeMustBeUnique(): void
    {
        Assert::same(
            $this->responseChecker->getError($this->client->getLastResponse()),
            'provinces[1].code: Province code must be unique.'
        );
    }

    private function getCountryCodeByName(string $countryName): string
    {
        $countryList = array_flip(Countries::getNames());
        Assert::keyExists(
            $countryList,
            $countryName,
            sprintf('The country with name "%s" not found', $countryName)
        );

        return $countryList[$countryName];
    }
}

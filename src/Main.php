<?php

namespace Claudusd\POC\Elasticsearch;

use Elastica\Client;
use Elastica\Document;
use Elastica\Index;
use Elastica\Type\Mapping;

class Main
{
    private $client;

    public function __construct()
    {
        $this->client = new Client(['host' => 'localhost']);
        $this->buildIndex($this->client);
        $this->populate();
    }

    private function buildIndex(Client $client)
    {
        $index = $client->getIndex('poc');
        $index->create(
            [
                'number_of_shards' => 4,
                'number_of_replicas' => 1,
                'analysis' => $this->buildAnanlyzer()
            ],
            true
        );
        $mapping = $this->buildType($index);
        $mapping->send();
    }

    private function buildType(Index $index)
    {
        $bookType = $index->getType('book');

        $mapping = new Mapping();
        $mapping->setType($bookType);

        $mapping->setProperties([
            'id' => ['type' => 'string', 'include_in_all' => false],
            'name' => ['type' => 'string', 'analyzer' => 'book_analyzer'],
        ]);

        return $mapping;
    }

    private function buildAnanlyzer()
    {
        return [
            'analyzer' => [
                'book_analyzer' => [
                    'type' => 'custom',
                    'tokenizer' => 'book_tokenizer',
                    'filter' => [
                        'book_lowercase', 'book_ngram'
                    ]
                ]
            ],
            'tokenizer' => [
                'book_tokenizer' => [
                    'type' => 'nGram',
                    'min_gram' => 2,
                    'max_gram' => 26,
                    'token_chars' => [ 'letter', 'digit', 'whitespace' ]
                ]
            ],
            'filter' => [
                'book_lowercase' => [
                    'type' => 'lowercase'
                ],
                'book_ngram' => [
                    'type' => 'nGram',
                    'min_gram' => 1,
                    'max_gram' => 2
                ]
            ]
        ];
    }

    private function populate()
    {
        $index = $this->client->getIndex('poc');
        $type = $index->getType('book');
        $type->addDocument(new Document('1', ['name' => 'Le Supplément d\'Âme']));
    }

    public function testSearch()
    {
        $index = $this->client->getIndex('poc');
        $type = $index->getType('book');

        $result = $type->search([
            'query' => [
                'query_string' => [
                    'query' => 'Le',
                    'analyzer' => 'book_analyzer'
                ]
            ],
        ]);

        var_dump($result);
    }
}
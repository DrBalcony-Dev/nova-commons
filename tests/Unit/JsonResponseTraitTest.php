<?php

use DrBalcony\NovaCommon\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseTraitTestClass
{
    use JsonResponseTrait;
}

it('sends a successful JSON response', function () {
    $trait = new JsonResponseTraitTestClass();
    $data = ['key' => 'value'];
    $response = $trait->sendResponse($data, 'Success message');

    expect($response)
        ->toBeInstanceOf(JsonResponse::class)
        ->getStatusCode()->toBe(Response::HTTP_OK)
        ->and($response->getContent())
        ->json()->toMatchArray([
            'success' => true,
            'message' => 'Success message',
            'data' => $data,
        ]);
});

it('sends a paginated JSON response', function () {
    // arrange
    $trait = new JsonResponseTraitTestClass();
    $paginator = new LengthAwarePaginator(
        items: [['id' => 1], ['id' => 2]],
        total: 10,
        perPage: 2,
        currentPage: 1,
        options: ['path' => 'http://example.com']
    );

    // act
    $response = $trait->sendPaginatedResponse(
        data: $paginator,
        message: 'Paginated data'
    );

    // assert
    expect($response)
        ->toBeInstanceOf(JsonResponse::class)
        ->getStatusCode()->toBe(Response::HTTP_OK)
        ->and($response->getContent())
        ->json()->toHaveKeys([
            'success',
            'message',
            'data',
            'meta'
        ])
        ->and($response->getContent())
        ->json()->meta->toHaveKeys([
            'current_page',
            'total_items',
            'per_page',
            'total_pages',
            'links'
        ]);
});

it('handles sending a raw error JSON response', function () {
    // arrange
    $trait = new JsonResponseTraitTestClass();
    $errorMessages = ['field' => 'required'];

    // act
    $response = $trait->sendError(
        error: 'Error occurred',
        errorMessages: $errorMessages,
        code: Response::HTTP_TOO_MANY_REQUESTS // sth other than default
    );

    // assert
    expect($response)
        ->toBeInstanceOf(JsonResponse::class)
        ->getStatusCode()->toBe(Response::HTTP_TOO_MANY_REQUESTS)
        ->and($response->getContent())
        ->json()->toMatchArray([
            'success' => false,
            'message' => 'Error occurred',
            'errors' => $errorMessages,
        ]);
});

it('transforms ResourceCollection data correctly', function () {
    $trait = new JsonResponseTraitTestClass();

    // Create a proper ResourceCollection with a collection resource
    $items = collect([
        ['id' => 1],
        ['id' => 2]
    ]);
    $collection = new class($items) extends ResourceCollection {
        public function toArray($request)
        {
            return $this->collection->all();
        }
    };

    $response = $trait->sendResponse($collection);

    expect($response->getContent())
        ->json()->data->toBe([
            ['id' => 1],
            ['id' => 2]
        ]);
});

it('throws exception for invalid paginator type', function () {
    $trait = new JsonResponseTraitTestClass();
    expect(fn () => $trait->sendPaginatedResponse('invalid'))
        ->toThrow(InvalidArgumentException::class, 'Invalid paginated data type');
});

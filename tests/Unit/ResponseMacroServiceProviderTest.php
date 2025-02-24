<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

beforeEach(function () {
    $this->app = createTestbenchApp()->createApplication();
});

it('registers success response macro', function () {
    $response = Response::success(['foo' => 'bar'], 'Success');
    expect($response)
        ->toBeInstanceOf(JsonResponse::class)
        ->getStatusCode()->toBe(SymfonyResponse::HTTP_OK)
        ->and($response->getContent())
        ->json()->toMatchArray([
            'success' => true,
            'message' => 'Success',
            'data' => ['foo' => 'bar'],
        ]);
});

it('registers paginate response macro', function () {
    $paginator = new LengthAwarePaginator(
        [['id' => 1]],
        5,
        1,
        1,
        ['path' => 'http://example.com']
    );
    $response = Response::paginate($paginator, 'Paginated');

    expect($response)
        ->getStatusCode()->toBe(SymfonyResponse::HTTP_OK)
        ->and($response->getContent())
        ->json()->meta->toHaveKey('total_items');
});

it('registers notFound response macro', function () {
    $response = Response::notFound('Resource not found');
    expect($response)
        ->getStatusCode()->toBe(SymfonyResponse::HTTP_NOT_FOUND)
        ->and($response->getContent())
        ->json()->toMatchArray([
            'success' => false,
            'message' => 'Resource not found',
        ]);
});

it('registers validationError response macro', function () {
    $errors = ['field' => 'is invalid'];
    $response = Response::validationError($errors, 'Validation failed');
    expect($response)
        ->getStatusCode()->toBe(SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY)
        ->and($response->getContent())
        ->json()->toMatchArray([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
        ]);
});

it('handles invalid collection type in paginate macro with exception', function () {
    expect(fn () => Response::paginate(
        paginate: new ResourceCollection([]),
        message: 'Paginated'
    ))->toThrow(InvalidArgumentException::class, 'Invalid paginated data type');
});

it('rejects non-string message in withoutData macro', function () {
    expect(fn () => Response::withoutData([123]))
        ->toThrow(\TypeError::class);
});

it('handles null message in success macro', function () {
    $response = Response::success(data: ['data' => 'test']);
    expect($response)
        ->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($response->getContent())
        ->json()->message->toBeNull();
});
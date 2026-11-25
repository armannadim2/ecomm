<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customers\Models\Customer;
use App\Http\Resources\Api\V1\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** /api/v1/customers — read only (personal data: give this scope only to systems that need it). */
class CustomerController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['email' => ['nullable', 'string', 'max:190']]);

        $query = Customer::query()->withCount('orders')->orderBy('id');
        $query->when($request->query('email'), fn ($q, $email) => $q->where('email', $email));
        $this->filterUpdatedSince($query, $request);

        return CustomerResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer->loadCount('orders'));
    }
}

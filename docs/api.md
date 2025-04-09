# Order API Documentation

## Base URL
```
http://your-domain.com/api
```

## Authentication
No authentication is required for this API.

## Endpoints

### List Orders
Retrieve a list of orders with optional filtering and sorting.

```http
GET /orders
```

#### Query Parameters
- `name` (optional): Filter orders by name (partial match)

#### Response
```json
[
    {
        "id": "uuid",
        "name": "string",
        "type": "connector|vpn_connection",
        "status": "ordered|processing|completed",
        "external_id": "string|null",
        "created_at": "datetime",
        "updated_at": "datetime"
    }
]
```

### Create Order
Create a new order.

```http
POST /orders
```

#### Request Body
```json
{
    "name": "string",
    "type": "connector|vpn_connection"
}
```

#### Response
```json
{
    "id": "uuid",
    "name": "string",
    "type": "connector|vpn_connection",
    "status": "ordered",
    "external_id": "string|null",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

### Get Order
Retrieve a specific order by ID.

```http
GET /orders/{id}
```

#### Response
```json
{
    "id": "uuid",
    "name": "string",
    "type": "connector|vpn_connection",
    "status": "ordered|processing|completed",
    "external_id": "string|null",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

### Delete Order
Delete a specific order. Only completed orders can be deleted.

```http
DELETE /orders/{id}
```

#### Response
- Status: 204 No Content (success)
- Status: 422 Unprocessable Entity (if order is not completed)

## Order Status Flow
1. `ordered`: Initial status when order is created
2. `processing`: Order is being processed by RedProviderPortal
3. `completed`: Order processing is finished

## Error Responses
```json
{
    "message": "Error description"
}
```

Common HTTP Status Codes:
- 400: Bad Request (invalid input)
- 404: Not Found
- 422: Unprocessable Entity (validation error)
- 500: Internal Server Error 
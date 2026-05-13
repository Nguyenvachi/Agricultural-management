# Agricultural Management System - Implementation Plan

## 1. Project Overview

### Objective
Xây dựng hệ thống Quản Lý Nông Sản phục vụ:
- Quản lý đại lý
- Quản lý sản phẩm nông sản
- Quản lý bảng giá
- Quản lý nhập / xuất kho
- Quản lý đơn hàng
- Theo dõi biến động tồn kho

---

# 2. Technology Stack

| Thành phần | Công nghệ |
|---|---|
| Backend | Laravel |
| Database | MySQL |
| Environment | Laragon |
| IDE | VSCode |
| Database Tool | HeidiSQL |
| Version Control | Git + GitHub |

---

# 3. Git Workflow

## Branch Structure

| Branch | Vai trò |
|---|---|
| main | Source ổn định production |
| dev | Nhánh phát triển chính |
| feature/* | Nhánh làm task |

---

## Development Flow

### 1. Pull source mới nhất

```bash
git checkout dev
git pull origin dev
```

---

### 2. Tạo feature branch

```bash
git checkout -b feature/[task-name]
```

Ví dụ:

```bash
git checkout -b feature/setup-models
```

---

### 3. Code + Test Local

- Code theo task
- Test local
- Kiểm tra DB
- Kiểm tra inventory flow

---

### 4. Commit + Push

```bash
git add .
git commit -m "feat: setup models"
git push origin feature/setup-models
```

---

### 5. Create Pull Request

- Merge vào branch `dev`
- Chờ review

---

### 6. Merge + Delete Feature Branch

Sau khi merge:
- Delete branch feature
- Pull lại branch dev

---

# 4. System Modules

## 4.1 Agency Module

### Chức năng
- CRUD đại lý
- Quản lý thông tin đại lý
- Trạng thái hoạt động

### Bảng liên quan
- agencies

---

## 4.2 User Module

### Chức năng
- CRUD người dùng
- Phân quyền role
- Liên kết đại lý

### Roles
| Role | Mô tả |
|---|---|
| ADMIN | Quản trị hệ thống |
| AGENCY | Đại lý |
| FARMER | Nông hộ |
| CUSTOMER | Khách hàng |

### Bảng liên quan
- users
- sys_lookup_values

---

## 4.3 Category Module

### Chức năng
- CRUD loại cây trồng

### Bảng liên quan
- categories

---

## 4.4 Item Module

### Chức năng
- CRUD sản phẩm
- Liên kết category
- Quản lý đơn vị tính

### Bảng liên quan
- items
- categories

---

## 4.5 Price List Module

### Chức năng
- Quản lý giá thu mua
- Quản lý giá bán
- Giá theo đại lý
- Giá theo thời gian hiệu lực

### Bảng liên quan
- price_lists

---

## 4.6 Order Module

### Chức năng
- Tạo đơn thu mua
- Tạo đơn bán hàng
- Chuyển kho nội bộ
- Trả hàng
- Điều chỉnh kho

### Order Types
| Type | Mô tả |
|---|---|
| PURCHASE_ORDER | Đơn nhập hàng |
| SALES_ORDER | Đơn bán hàng |
| INTERNAL_TRANSFER | Chuyển kho |
| RETURN_ORDER | Trả hàng |
| ADJUSTMENT_ORDER | Điều chỉnh kho |

### Order Status
| Status | Mô tả |
|---|---|
| PENDING | Chờ xử lý |
| PROCESSING | Đang xử lý |
| COMPLETED | Hoàn thành |
| CANCELLED | Đã hủy |

### Bảng liên quan
- orders
- order_details

---

## 4.7 Inventory Module

### Chức năng
- Theo dõi tồn kho hiện tại
- Ghi nhận biến động kho
- Theo dõi nhập / xuất
- Quản lý tồn kho theo đại lý

### Inventory Logic

## inventories
Lưu tồn kho hiện tại.

## inventory_transactions
Lưu lịch sử biến động kho.

### Transaction Types
| Type | Mô tả |
|---|---|
| IMPORT | Nhập kho |
| EXPORT | Xuất kho |

### Bảng liên quan
- inventories
- inventory_transactions

---

# 5. E2E Business Flow

## 5.1 Luồng nhập hàng từ nông hộ

### Flow

1. Nông hộ bán nông sản
2. Đại lý tạo PURCHASE_ORDER
3. Tạo order_details
4. Tăng inventories.quantity
5. Ghi inventory_transactions
6. Cập nhật total_amount
7. Hoàn thành đơn

---

## 5.2 Luồng bán hàng

### Flow

1. Khách hàng đặt hàng
2. Đại lý tạo SALES_ORDER
3. Kiểm tra tồn kho
4. Trừ inventories.quantity
5. Ghi inventory_transactions
6. Hoàn thành đơn

---

## 5.3 Luồng chuyển kho nội bộ

### Flow

1. Đại lý A tạo INTERNAL_TRANSFER
2. Trừ tồn kho đại lý A
3. Tăng tồn kho đại lý B
4. Ghi inventory_transactions cho cả 2 kho
5. Hoàn thành đơn

---

# 6. Implementation Phases

# Phase 1 - Foundation

## Mục tiêu
Setup nền hệ thống.

### Tasks
- Setup Laravel
- Setup Git workflow
- Setup database
- Setup models
- Setup relationships
- Setup constants

---

# Phase 2 - Master Data CRUD

## Mục tiêu
Hoàn thiện CRUD dữ liệu nền.

### Modules
- Agency
- User
- Category
- Item
- Price List

### Tasks
- Models
- Controllers
- Validation
- Views
- CRUD logic

---

# Phase 3 - Inventory Core

## Mục tiêu
Xây dựng logic tồn kho.

### Tasks
- InventoryService
- Stock update logic
- Inventory transaction logic
- Inventory validation
- Stock checking

### Quan trọng
Toàn bộ inventory update phải chạy trong:

```php
DB::transaction()
```

---

# Phase 4 - Order Flow

## Mục tiêu
Hoàn thiện nghiệp vụ nhập / xuất.

### Tasks
- Purchase order
- Sales order
- Internal transfer
- Order detail logic
- Total amount calculation
- Inventory integration

---

# Phase 5 - Validation & Optimization

## Mục tiêu
Ổn định hệ thống.

### Tasks
- Validation
- Exception handling
- Optimize queries
- Refactor services
- UI cleanup
- Testing

---

# 7. MVC Project Structure

## Architecture Direction

Hệ thống triển khai theo kiến trúc MVC truyền thống của Laravel.

Mục tiêu:
- Dễ maintain
- Dễ onboarding
- Dễ review code
- Phù hợp scope intern project
- Phù hợp yêu cầu leader
- Tối ưu cho CRUD + Inventory Flow

Không triển khai theo hướng:
- REST API first
- Microservice
- Clean Architecture phức tạp
- DDD
- CQRS

---

# 8. MVC Project Structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── AgencyController.php
│   │   ├── CategoryController.php
│   │   ├── ItemController.php
│   │   ├── PriceListController.php
│   │   ├── OrderController.php
│   │   └── InventoryController.php
│   │
│   └── Requests/
│       ├── Agency/
│       ├── Category/
│       ├── Item/
│       ├── PriceList/
│       └── Order/
│
├── Models/
│   ├── Agency.php
│   ├── User.php
│   ├── Category.php
│   ├── Item.php
│   ├── PriceList.php
│   ├── Order.php
│   ├── OrderDetail.php
│   ├── Inventory.php
│   ├── InventoryTransaction.php
│   ├── SysLookupType.php
│   └── SysLookupValue.php
│
├── Services/
│   ├── InventoryService.php
│   └── OrderService.php
│
├── Constants/
│   └── LookupCode.php
│
├── Helpers/
│
└── Traits/
```

---

## MVC Responsibility

### Model

Vai trò:
- Mapping database table
- Relationship
- Scope query
- Accessor / Mutator

Không xử lý:
- Business logic phức tạp
- Inventory transaction
- Order flow lớn

---

### Controller

Vai trò:
- Nhận request
- Validate request
- Gọi model/service
- Return view

Không xử lý:
- Inventory calculation
- Stock update logic lớn
- Business transaction phức tạp

---

### View (Blade)

Vai trò:
- Hiển thị dữ liệu
- Form CRUD
- Table listing
- UI validation message

Không xử lý:
- Query database
- Business logic

---

### Service

Chỉ dùng cho nghiệp vụ phức tạp.

Hiện tại chỉ cần:

| Service | Vai trò |
|---|---|
| InventoryService | Xử lý tồn kho |
| OrderService | Xử lý tạo đơn + cập nhật kho |

Không cần tạo quá nhiều service.

---

## Folder Structure Theo Module

### Agency Module

```text
app/
├── Http/Controllers/AgencyController.php
├── Http/Requests/Agency/
├── Models/Agency.php
└── resources/views/agency/
```

---

### Item Module

```text
app/
├── Http/Controllers/ItemController.php
├── Http/Requests/Item/
├── Models/Item.php
└── resources/views/item/
```

---

### Order Module

```text
app/
├── Http/Controllers/OrderController.php
├── Http/Requests/Order/
├── Models/Order.php
├── Models/OrderDetail.php
├── Services/OrderService.php
├── Services/InventoryService.php
└── resources/views/order/
```

---

## View Structure

```text
resources/views/
├── layouts/
│   └── app.blade.php
│
├── agency/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
│
├── category/
├── item/
├── price-list/
├── order/
└── inventory/
```

---

## Route Structure

```php
Route::resource('agencies', AgencyController::class);
Route::resource('categories', CategoryController::class);
Route::resource('items', ItemController::class);
Route::resource('price-lists', PriceListController::class);
Route::resource('orders', OrderController::class);
```

---

## Coding Direction

### CRUD đơn giản

Controller -> Model -> View

Ví dụ:
- Agency CRUD
- Category CRUD
- Item CRUD

---

### CRUD có nghiệp vụ

Controller -> Service -> Model -> DB Transaction

Ví dụ:
- Order
- Inventory
- Internal Transfer

---

## Inventory Logic Direction

Inventory là core nghiệp vụ.

Không xử lý trực tiếp trong Controller.

Flow đúng:

```text
OrderController
    ↓
OrderService
    ↓
InventoryService
    ↓
Inventory + InventoryTransaction
```

---

## AI / Copilot Coding Context

Project đang theo:
- Laravel MVC
- Blade template
- Server Rendered UI
- CRUD-based system
- MySQL relational database
- DB-first architecture

Không theo:
- SPA
- React
- Vue API-first
- Clean architecture phức tạp
- Repository pattern overkill

### 3 QUY TẮC BẤT DI BẤT DỊCH (CORE PRINCIPLES)
1. **Luôn bám sát project, không được lệch lạc:** Tuân thủ nghiêm ngặt định hướng và quy tắc đề ra.
2. **Khi hướng dẫn phải luôn nêu rõ file mẹ/con:** Mọi thay đổi/đề xuất đều phải nói rõ vị trí file mẹ hoặc con. Luôn giao tiếp bằng TIẾNG VIỆT.
3. **Chỉ được thêm code, không được bớt code:** Mọi cải tiến chỉ bổ sung, tuyệt đối không xóa/giảm bớt code hiện có. KHÔNG ĐƯỢC SỬ DỤNG LỆNH "fresh DB".

---

## Important Development Rules

### 1. Không code business logic lớn trong Blade

Sai:

```php
@if($inventory > 0)
```

Đúng:

Controller xử lý trước rồi truyền view.

---

### 2. Không update tồn kho trực tiếp trong Controller

Sai:

```php
$inventory->quantity -= 10;
$inventory->save();
```

Đúng:

```php
InventoryService::exportStock();
```

---

### 3. Tất cả inventory flow phải dùng transaction

```php
DB::transaction(function () {
    // order logic
    // inventory logic
});
```

---

### 4. Không over-engineer

Ưu tiên:
- dễ đọc
- dễ maintain
- đúng nghiệp vụ
- đúng flow MVC

Không cần:
- pattern phức tạp
- abstraction quá mức
- generic system overkill

---

# 9. Service Layer Design

## Services

| Service | Vai trò |
|---|---|
| AgencyService | Xử lý đại lý |
| ItemService | Xử lý sản phẩm |
| PriceService | Xử lý bảng giá |
| InventoryService | Xử lý tồn kho |
| OrderService | Xử lý đơn hàng |

---

# 9. Important Rules

## Inventory Rules

- Không update tồn kho trực tiếp trong Controller
- Mọi update kho phải đi qua InventoryService
- Mọi order phải ghi inventory_transactions
- Không cho xuất kho âm

---

## Database Rules

- Không hard delete dữ liệu nghiệp vụ
- Dùng Soft Delete
- Dùng transaction cho nghiệp vụ kho
- Không query business logic trong Blade

---

## Code Rules

- Controller chỉ xử lý request/response
- Business logic đặt trong Service
- Validation tách Request riêng
- Reusable logic đặt trong Trait/Helper

---

# 10. Initial Development Priority

## Ưu tiên triển khai

### Step 1
- Models
- Relationships
- Constants

### Step 2
- Agency CRUD
- Category CRUD
- Item CRUD

### Step 3
- Price List CRUD

### Step 4
- Inventory logic

### Step 5
- Order flow

---

# 11. Expected Outcome

Sau khi hoàn thành hệ thống:
- CRUD ổn định
- Quản lý tồn kho chính xác
- Theo dõi nhập/xuất rõ ràng
- Hỗ trợ nhiều đại lý
- Có khả năng mở rộng mobile/API sau này


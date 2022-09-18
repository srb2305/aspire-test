Code developed by: Saurabh Sahu
Email : web.saurabhsahu@gmail.com

## Dependencies what i used
laravel/passport (For api security purpose)

## My Steps for complete this project
-setup laravel project to local
-setup .env file
-composer require laravel/passport
-php artisan migrate
-php artisan passport:install
-update App/Providers/AuthServiceProvider
-update config/auth (for passport driver)

-php artisan make:controller Auth/UserAuthController (for register/login customer)

-php artisan make:model CustomerLoan -m
-php artisan make:model ScheduledRepayment -m

-php artisan make:resource LoanResource
-php artisan make:controller LoanController

-updated/created migration files
-php artisan migrate
-create routes (routes/api)
-Created Traits/CommonTrait for code reuse.
-php artisan serve (http://127.0.0.1:8000)

## APIs security changes on POSTMAN

1) From postman change 
Headers/Accept => application/json

2) After register/login api, you will get a token on responce, For all other apis call you need to pass Bearer Token.
click on the authorization tab in Postman, select Bearer Token on the Type dropdown list, and paste your token inside the Token field.

## POSTMAN Collection Link:
https://www.getpostman.com/collections/a00bff8d83e924968bc5

## REST APIs Details
1) Register new customer:
http://127.0.0.1:8000/api/register
POST [name,email,password,password_confirmation]

user's default role will be 'CUSTOMER' for admin role pls change any 1 user role to 'ADMIN' from database. 

2) Login customer/admin:
http://127.0.0.1:8000/api/login
POST [email,password]

3) Customer create a loan:
http://127.0.0.1:8000/api/loan_request
POST [amount,term]  
Please send Customer Bearer Token with this api

4) Admin approve the loan:
http://127.0.0.1:8000/api/loan_approve
POST [loan_id]  
Please send Admin Bearer Token with this api

5) Customer can view loan belong to him:
http://127.0.0.1:8000/api/loan_details
POST [loan_id] 
Please send Customer Bearer Token with this api

6) Customer add a repayments:
http://127.0.0.1:8000/api/loan_repayment
POST [loan_id, amount] 
Please send Customer Bearer Token with this api

## Nice-to-have:
1) Include brief documentation for the project: the choices you made and why
- I used Laravel for this project because laravel is fully secure and best framework of PHP. 
- I used Passport package for rest api security.
- I used Traits for code reusability,standard code style, readable, easy to review & understand.
- Added proper comments where needed.
- Added proper validations and error message. 
- Used proper migration

2) Script to install the app in one go (any tool)
- Command Prompt, Composer, SQL
php artisan serve

3) Postman collection/openAPI document for the API 
- https://www.getpostman.com/collections/a00bff8d83e924968bc5

4) Clean application architecture / design patterns
- Yes i used clear application architecture.


## Developed by: Saurabh Sahu
For any queries pls mail : web.saurabhsahu@gmail.com
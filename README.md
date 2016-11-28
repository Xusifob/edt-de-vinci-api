# Description

This API allows you to get the calendar data from 
- BCIT (British Columbia Instutut of Technology, Vancouver) 
- Pole Léonard De Vinci, Paris

# API 

Each Route must have a GET school parameter with the id of the school

**LOGIN**
------
Login the user and return its id

* **URL**

  /V2/login.php

* **Method:**

  `POST`
  
*  **URL Params**

   **Required:**
 
   `school=[string]`
   

* **Data Params**

    ```json
       {
        "login" : "string",
        "pass" : "string"
        }
    ```


* **Success Response:**

  * **Code:** 200 <br />
    **Content:** `{ id : "12FG454353454353"", status : 200 }`
 
* **Error Response:**

  * **Code:** 400 BAD REQUEST <br />
    **Content:** `{status, 400 error : "SCHOOL_REQUIRED" }`

  OR

  * **Code:** 401 UNAUTHORIZED <br />
    **Content:** `{status, 401 error : "ERROR_ID_PASSWORD_INCORRECT" }`

    * **Sample Call:**

    ```javascript
      $.ajax({
        url: "/V2/login.php?school=bcit",
        dataType: "json",
        type : "POST",
          data: { login: "John", pass: "Boston" }
        success : function(r) {
          console.log(r);
        }
      });
    ```
  
**EVENTS**
------
Returns the events of the calendar
  
  * **URL**
  
    /V2/events.php
  
  * **Method:**
  
    `POST`
    
  *  **URL Params**
  
     **Required:**
   
     `school=[string]`
     
  
  * **Data Params**
  
    ```json
       {
        "login" : "string",
        "pass" : "string",
        "id" : "string"
        }
    ```
  
  * **Success Response:**
  
    * **Code:** 200 <br />
      **Content:** `{ data : [...], status : 200 }`
   
  * **Error Response:**
  
    * **Code:** 400 BAD REQUEST <br />
      **Content:** `{status, 400 error : "SCHOOL_REQUIRED" }`
  
    OR
  
    * **Code:** 401 UNAUTHORIZED <br />
      **Content:** `{status, 401 error : "ERROR_ID_PASSWORD_INCORRECT" }`
  
  * **Sample Call:**
  
    ```javascript
      $.ajax({
        url: "/V2/events.php?school=bcit",
        dataType: "json",
        type : "POST",
          data: { login: "John", pass: "Boston","id" : "4355464563465464GDF" }
        success : function(r) {
          console.log(r);
        }
      });
    ```
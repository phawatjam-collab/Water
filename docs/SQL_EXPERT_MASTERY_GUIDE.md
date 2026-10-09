# The Ultimate SQL Mastery Guide: จากศูนย์สู่ Expert SQL
**คู่มือพิชิต SQL ฉบับสมบูรณ์: เจาะลึกตรรกะ ไวยากรณ์ กลไกการทำงาน และคลังโจทย์ 20 ข้อระดับเทพ**

---

## สารบัญเนื้อหา (Table of Contents)
1. **Part 1: The Core Philosophy & Execution Order (สมองกลของ SQL)**
   - Declarative vs Imperative
   - ลำดับการประมวลผลคำสั่งจริง (Logical Query Processing Order)
2. **Part 2: Precision Filtering & Logic (การกรองข้อมูลแบบแม่นยำ)**
   - Operators & Precedence (`AND`, `OR`, `NOT`, การใส่วงเล็บ)
   - Three-Valued Logic (3VL) & ความจริงของ `NULL`
   - String Pattern Matching (`LIKE`, `_`, `%`, `IN`, `BETWEEN`)
3. **Part 3: Mastering Dates & Time (เซียนวัน-เวลา-อายุงาน)**
   - การคำนวณอายุงาน / อายุคน / ระยะเวลา (4 วิธีที่แตกต่าง)
   - ฟังก์ชันวันที่: `YEAR()`, `MONTH()`, `TIMESTAMPDIFF()`, `DATEDIFF()`
4. **Part 4: Aggregation & Grouping (การรวมข้อมูลและสถิติ)**
   - Aggregate Functions (`COUNT(*)`, `SUM`, `AVG`, `MIN`, `MAX`)
   - กฎเหล็กของ `GROUP BY`
   - ศึกชิงบัลลังก์: `WHERE` vs `HAVING`
   - Conditional Aggregation (`SUM(CASE WHEN...)`)
5. **Part 5: Relational Joins (เชื่อมโยงมิติข้อมูล)**
   - `INNER JOIN`, `LEFT JOIN`, `RIGHT JOIN`, `FULL JOIN`, `CROSS JOIN`, `SELF JOIN`
   - เงื่อนไขใน `ON` vs `WHERE` ของ `LEFT JOIN` (จุดปราบเซียน)
6. **Part 6: Subqueries & CTEs (คิวรีย่อยและตารางเสมือน)**
   - Scalar Subquery, Correlated Subquery, `EXISTS` vs `IN`
   - Common Table Expressions (`WITH ... AS`)
7. **Part 7: Window Functions (อาวุธลับของ SQL Expert)**
   - `OVER (PARTITION BY ... ORDER BY ...)`
   - `ROW_NUMBER()`, `RANK()`, `DENSE_RANK()`, `LEAD()`, `LAG()`
8. **Part 8: Performance & SARGable Queries (เขียนให้เร็วระดับแสง)**
   - Search Argument Able (SARGable) Concept
9. **Part 9: คลังโจทย์ฝึกทำ 20 ข้อ ไล่ระดับ Easy $\rightarrow$ Boss Level**
   - พร้อมเฉลยละเอียดและวิเคราะห์ทีละบรรทัด

---

# Part 1: The Core Philosophy & Execution Order (สมองกลของ SQL)

### 1.1 SQL ต่างจากภาษาเขียนโปรแกรมทั่วไปอย่างไร?
* **ภาษาทั่วไป (Python, C, PHP, Java) เป็น Imperative Language:** เราต้องสั่งคอมพิวเตอร์ว่า *"ทำอย่างไร (How)"* เช่น วนลูป `for`, เช็ค `if`, เก็บตัวแปร
* **SQL เป็น Declarative Language:** เราสั่ง Database Engine ว่า *"เราต้องการอะไร (What)"* ส่วน Database Optimizer จะไปคิดอัลกอริทึมในการค้นหาข้อมูลที่เร็วที่สุดในฮาร์ดดิสก์เอง

---

### 1.2 ลำดับการประมวลผลคำสั่งจริง (Logical Query Processing Order)
จุดที่ทำให้คนสับสนที่สุดคือ **"ลำดับที่เราพิมพ์" ไม่ตรงกับ "ลำดับที่ Database ประมวลผลจริง"!**

```
ลำดับการเขียนโค้ด (Syntax Order)          ลำดับการทำงานจริงในระบบ (Execution Order)
----------------------------------          -----------------------------------------
1. SELECT                                   1. FROM & JOIN     (หาตารางและเชื่อมข้อมูล)
2. FROM & JOIN                              2. WHERE           (กรองแถวที่ไม่ต้องการออก)
3. WHERE                                    3. GROUP BY        (จัดกลุ่มข้อมูล)
4. GROUP BY                                 4. HAVING          (กรองกลุ่มที่คำนวณแล้ว)
5. HAVING                                   5. SELECT          (เลือกคอลัมน์/คำนวณสูตร)
6. ORDER BY                                 6. DISTINCT        (ตัดแถวซ้ำ)
7. LIMIT / OFFSET                           7. ORDER BY        (เรียงลำดับผลลัพธ์)
                                            8. LIMIT / OFFSET  (ตัดจำนวนแถวที่จะส่งออก)
```

> 💡 **ทำไมเราถึงใช้ Alias (ชื่อย่อ) ใน WHERE ไม่ได้?**
> เช่น `SELECT salary * 12 AS annual_sal FROM emp WHERE annual_sal > 500000;` $\rightarrow$ **ERROR!**
> **เหตุผล:** เพราะระบบทำงาน `WHERE` (ขั้นตอนที่ 2) **ก่อน**ที่จะรู้จักชื่อ `annual_sal` ใน `SELECT` (ขั้นตอนที่ 5)!

---

# Part 2: Precision Filtering & Logic (การกรองข้อมูล)

### 2.1 Operators & Precedence (ลำดับความสำคัญของตรรกศาสตร์)
ลำดับการประเมินค่าตรรกะคือ:
$$\text{วงเล็บ } () \quad \longrightarrow \quad \text{NOT} \quad \longrightarrow \quad \text{AND} \quad \longrightarrow \quad \text{OR}$$

> ⚠️ **กฎทองคำ:** เมื่อใดก็ตามที่มี `OR` อยู่ปนกับ `AND` **ต้องใส่วงเล็บเสมอ!**

* **ตัวอย่างข้อผิดพลาดคลาสสิก:**
  ```sql
  -- โจทย์: หาพนักงานแผนก IT หรือ HR ที่มีเงินเดือนมากกว่า 40,000
  -- เขียนผิด (ไม่มีวงเล็บ):
  SELECT * FROM employees 
  WHERE dept = 'IT' OR dept = 'HR' AND salary > 40000;
  -- ความหมายจริง: (ทุกคนในแผนก IT เงินเดือนเท่าไรก็เอา) หรือ (คนใน HR ที่เงินเดือนเกิน 40,000)

  -- เขียนถูก (ใส่วงเล็บ):
  SELECT * FROM employees 
  WHERE (dept = 'IT' OR dept = 'HR') AND salary > 40000;
  ```

---

### 2.2 Three-Valued Logic (3VL) และความจริงของ `NULL`
ใน SQL ค่าตรรกะไม่ได้มีแค่ `TRUE` กับ `FALSE` แต่มี **`UNKNOWN`** อีกตัวหนึ่ง!
* `NULL` ไม่ใช่เลข 0 และไม่ใช่ข้อความว่าง `""` แต่คือ **"ไม่รู้ค่า (Unknown/Missing Value)"**
* กฎเหล็ก: **อะไรก็ตามที่เอาไปเปรียบเทียบกับ `NULL` จะได้ผลลัพธ์เป็น `UNKNOWN` เสมอ!**
  * `5 = NULL` $\rightarrow$ `UNKNOWN`
  * `NULL = NULL` $\rightarrow$ `UNKNOWN` (ไม่ใช่ TRUE!)
  * `NULL <> 10` $\rightarrow$ `UNKNOWN`
* **คำสั่ง `WHERE` จะดึงเฉพาะแถวที่เงื่อนไขเป็น `TRUE` เท่านั้น (แถวที่เป็น `FALSE` หรือ `UNKNOWN` จะถูกทิ้งทั้งหมด!)**
  ```sql
  -- ผิดมหันต์ (จะไม่ได้ข้อมูลอะไรออกมาเลย):
  SELECT * FROM employees WHERE manager_id = NULL;

  -- ถูกต้อง 100%:
  SELECT * FROM employees WHERE manager_id IS NULL;
  SELECT * FROM employees WHERE manager_id IS NOT NULL;
  ```

---

### 2.3 การกรองข้อความ (Pattern Matching)
* **`LIKE` Wildcards:**
  * `%` แทนตัวอักษรใดๆ กี่ตัวก็ได้ (0 ตัวขึ้นไป)
  * `_` (Underscore) แทนตัวอักษรใดๆ **1 ตัวพอดีเป๊ะ**
* **รูปแบบการใช้งาน:**
  * `LIKE 'ก%'` $\rightarrow$ ขึ้นต้นด้วย 'ก'
  * `LIKE '%ทอง'` $\rightarrow$ ลงท้ายด้วย 'ทอง'
  * `LIKE '%สม%'` $\rightarrow$ มีคำว่า 'สม' อยู่ตรงไหนก็ได้
  * `LIKE '___'` $\rightarrow$ ข้อความที่มีความยาว 3 ตัวอักษรพอดี
  * `LIKE 'B_ll'` $\rightarrow$ ตัวแรก B ตัวที่สาม l ตัวที่สี่ l เช่น Bill, Ball, Bell

---

# Part 3: Mastering Dates & Time (เซียนวัน-เวลา-อายุงาน)

### 3.1 การคำนวณอายุงาน / อายุคน (4 ระดับความเทพ)

| ระดับ | วิธีการคำนวณ | ตัวอย่างโค้ด SQL | ข้อดี/ข้อจำกัด |
| :---: | :--- | :--- | :--- |
| **ระดับ 1**<br>(พื้นฐาน) | เอาปีเป้าหมายลบด้วย `YEAR(col)` | `2024 - YEAR(hire_date)` | ง่าย เหมาะกับข้อสอบที่โจทย์ระบุปีคงที่มาให้ |
| **ระดับ 2**<br>(ปีปัจจุบัน) | เอาปีปัจจุบันลบด้วยปีที่เริ่มงาน | `YEAR(CURDATE()) - YEAR(hire_date)` | ใช้กับระบบจริงที่ต้องการปีปัจจุบันอัตโนมัติ |
| **ระดับ 3**<br>(มือโปรเป๊ะวัน) | คำนวณปีเต็มด้วย `TIMESTAMPDIFF` | `TIMESTAMPDIFF(YEAR, hire_date, CURDATE())` | **แม่นยำที่สุด!** ถ้ายังไม่ถึงวันครบรอบวันเกิด/วันเริ่มงาน จะยังไม่นับเป็น 1 ปีเต็ม |
| **ระดับ 4**<br>(นับจำนวนวัน) | หาจำนวนวันที่ผ่านไปด้วย `DATEDIFF` | `DATEDIFF(CURDATE(), hire_date)` | ได้ผลลัพธ์เป็นจำนวนวัน (Days) |

```sql
-- ตัวอย่างเปรียบเทียบ: คนเริ่มงานวันที่ 2020-12-01 ถ้าคำนวณ ณ วันที่ 2024-05-01
-- วิธี YEAR() - YEAR(): 2024 - 2020 = 4 ปี (คลาดเคลื่อน เพราะยังไม่ถึงเดือนธันวาคม)
-- วิธี TIMESTAMPDIFF(YEAR, ...): จะได้ 3 ปี (ถูกต้องตามกฎหมายแรงงานเป๊ะ!)
SELECT 
    emp_name,
    hire_date,
    (YEAR(CURDATE()) - YEAR(hire_date)) AS rough_years,
    TIMESTAMPDIFF(YEAR, hire_date, CURDATE()) AS exact_years,
    DATEDIFF(CURDATE(), hire_date) AS total_days
FROM employees;
```

---

# Part 4: Aggregation & Grouping (การรวมข้อมูลและสถิติ)

### 4.1 Aggregate Functions หัวใจ 5 ตัว
* `COUNT(*)`: นับจำนวนแถวทั้งหมด (รวมแถวที่มี NULL)
* `COUNT(column)`: นับเฉพาะแถวที่ column นั้น **ไม่ใช่ NULL**
* `COUNT(DISTINCT column)`: นับเฉพาะค่าที่ไม่ซ้ำกัน และไม่ใช่ NULL
* `SUM(column)`, `AVG(column)`: คำนวณผลรวมและค่าเฉลี่ย (ข้ามค่า NULL อัตโนมัติ)
* `MIN(column)`, `MAX(column)`: หาค่าน้อยสุดและมากสุด

---

### 4.2 กฎเหล็กของ `GROUP BY`
> **The Golden Rule:** คอลัมน์ใดก็ตามที่อยู่ใน `SELECT` หากไม่ได้ถูกครอบด้วย Aggregate Function (`SUM`, `AVG`, `COUNT` ฯลฯ) **คอลัมน์นั้น "ต้อง" ถูกใส่ไว้ใน `GROUP BY` เสมอ!**

```sql
-- ผิด: emp_name ไม่ได้อยู่ใน GROUP BY และไม่ได้ถูก aggregate
SELECT department, emp_name, AVG(salary) 
FROM employees 
GROUP BY department;

-- ถูกต้อง:
SELECT department, AVG(salary) AS avg_sal, COUNT(*) AS emp_count
FROM employees 
GROUP BY department;
```

---

### 4.3 ศึกชิงบัลลังก์: `WHERE` vs `HAVING` ต่างกันอย่างไร?

| คุณสมบัติ | `WHERE` | `HAVING` |
| :--- | :--- | :--- |
| **จังหวะการทำงาน** | ทำงาน **ก่อน** `GROUP BY` (กรองระดับรายแถว) | ทำงาน **หลัง** `GROUP BY` (กรองระดับกลุ่ม) |
| **การใช้ฟังก์ชันสถิติ** | ❌ **ห้ามใช้** `SUM`, `AVG`, `COUNT` | ✅ **ใช้ได้** กรองผลลัพธ์จากการคำนวณกลุ่ม |
| **การใช้งานหลัก** | คัดแถวที่ไม่เกี่ยวข้องออกก่อนนำไปรวม | คัดเฉพาะกลุ่มที่มีผลรวมหรือค่าเฉลี่ยตามเกณฑ์ |

```sql
-- หาแผนกที่มีเงินเดือนเฉลี่ย > 35,000 โดยไม่นำพนักงานทดลองงาน (status = 'PROBATION') มาคิด
SELECT department, AVG(salary) AS avg_sal
FROM employees
WHERE status <> 'PROBATION'           -- 1. กรองแถวก่อนรวมกลุ่ม
GROUP BY department
HAVING AVG(salary) > 35000;           -- 2. กรองกลุ่มหลังคำนวณเสร็จ
```

---

# Part 5: Relational Joins (เชื่อมโยงมิติข้อมูล)

### 5.1 สรุปชนิดของ JOIN
```
    [ ตาราง A ]              [ ตาราง B ]
     (   A   [  A ∩ B  ]   B   )
```
1. **`INNER JOIN`:** คืนเฉพาะแถวที่ Primary Key และ Foreign Key ตรงกันทั้งสองฝั่ง ($A \cap B$)
2. **`LEFT (OUTER) JOIN`:** คืนข้อมูลฝั่งซ้าย (ตารางหลัก) ทุกแถว หากฝั่งขวาไม่มีข้อมูลคู่กัน จะเติม `NULL` ให้ฝั่งขวา
3. **`RIGHT (OUTER) JOIN`:** เหมือน LEFT JOIN แต่กลับทิศ (ไม่นิยม นิยมใช้ LEFT JOIN สลับชื่อตารางแทน)
4. **`FULL OUTER JOIN`:** คืนทุกแถวจากทั้งสองฝั่ง ฝั่งไหนไม่มีคู่ให้เติม `NULL`
5. **`CROSS JOIN`:** Cartesian Product นำทุกแถวของ A จับคู่กับทุกแถวของ B ($m \times n$ แถว)
6. **`SELF JOIN`:** การนำตารางเดิมมา JOIN กับตัวเอง (มักใช้ตารางพนักงานกับหัวหน้างาน)

---

### 5.2 จุดปราบเซียน: `ON` vs `WHERE` ใน `LEFT JOIN`
นี่คือข้อสอบที่แยกเด็กทั่วไปออกจากระดับ Expert:
```sql
-- แบบที่ 1: ใส่เงื่อนไขใน ON
SELECT c.cus_id, c.name, o.order_id, o.amount
FROM customers c
LEFT JOIN orders o ON c.cus_id = o.cus_id AND o.amount > 1000;
-- ผลลัพธ์: ลูกค้าทุกคนจะยังอยู่ครบ! แต่เฉพาะออเดอร์ที่ > 1000 ถึงจะถูกดึงมาจับคู่ (ถ้าไม่มีคู่ คอลัมน์ order จะเป็น NULL)

-- แบบที่ 2: ใส่เงื่อนไขใน WHERE
SELECT c.cus_id, c.name, o.order_id, o.amount
FROM customers c
LEFT JOIN orders o ON c.cus_id = o.cus_id
WHERE o.amount > 1000;
-- ผลลัพธ์: กลายสภาพเป็น INNER JOIN ทันที! ลูกค้าที่ไม่มีออเดอร์หรือออเดอร์ <= 1000 จะถูกกรองทิ้งหมด!
```

---

# Part 6: Subqueries & CTEs (คิวรีย่อยและตารางเสมือน)

### 6.1 Common Table Expressions (CTE) — พระเอกของ Modern SQL
แทนที่จะเขียน Subquery ซ้อนกันจนลายตา CTE ใช้คำสำคัญ `WITH` เพื่อสร้างตารางชั่วคราวให้อ่านง่ายเป็นทอดๆ:

```sql
WITH HighEarningDept AS (
    SELECT department, AVG(salary) AS avg_sal
    FROM employees
    GROUP BY department
    HAVING AVG(salary) > 40000
),
EmployeeCount AS (
    SELECT department, COUNT(*) AS total_staff
    FROM employees
    GROUP BY department
)
SELECT h.department, h.avg_sal, e.total_staff
FROM HighEarningDept h
JOIN EmployeeCount e ON h.department = e.department;
```

---

# Part 7: Window Functions (อาวุธลับของ SQL Expert)

Window Functions ทำการคำนวณข้ามแถว **โดยไม่ยุบแถวรวมกันเหมือน `GROUP BY`** ทำให้ข้อมูลเดิมยังอยู่ครบทุกบรรทัด!

### 7.1 โครงสร้างคำสั่ง
$$\text{FUNCTION}() \quad \text{OVER} \quad (\text{PARTITION BY } col \quad \text{ORDER BY } col)$$
* `PARTITION BY`: แบ่งกลุ่มข้อมูล (คล้าย GROUP BY ย่อยๆ ภายในแถว)
* `ORDER BY`: กำหนดลำดับการรันคำนวณภายในกลุ่ม

### 7.2 ตารางเปรียบเทียบการจัดอันดับ (Ranking)
สมมติคะแนน: `[100, 90, 90, 80]`
* **`ROW_NUMBER()`:** `1, 2, 3, 4` (รันเลข 1..N ไม่สนคะแนนซ้ำ)
* **`RANK()`:** `1, 2, 2, 4` (คะแนนเท่ากันอันดับเท่ากัน แต่อันดับถัดไปจะกระโดดข้าม)
* **`DENSE_RANK()`:** `1, 2, 2, 3` (คะแนนเท่ากันอันดับเท่ากัน แต่อันดับถัดไป**ไม่กระโดดข้าม**)

---

# Part 8: Performance & SARGable Queries (เคล็ดลับความเร็ว)

คำว่า **SARGable** ย่อมาจาก **Search Argument Able** หมายถึง การเขียนเงื่อนไขใน `WHERE` ที่ฐานข้อมูลสามารถนำ **B-Tree Index** มากระโดดหาข้อมูลได้ทันที โดยไม่ต้องสแกนทุกบรรทัดในฮาร์ดดิสก์ (Full Table Scan)

```sql
-- ❌ NON-SARGABLE (ช้ามาก ฐานข้อมูลต้องคำนวณฟังก์ชันทุกแถว Index ไร้ค่า):
WHERE YEAR(created_at) = 2024;
WHERE SUBSTRING(phone, 1, 3) = '081';
WHERE salary * 1.1 > 50000;

-- ✅ SARGABLE (เร็วแสง ใช้ Index ได้เต็ม 100%):
WHERE created_at >= '2024-01-01' AND created_at < '2025-01-01';
WHERE phone LIKE '081%';
WHERE salary > 50000 / 1.1;
```

---

# Part 9: รวมโจทย์ฝึกทำ 20 ข้อ โหด ครบ ทุกมิติ (Easy $\rightarrow$ Boss Level)

### โครงสร้างฐานข้อมูลสำหรับฝึกทำโจทย์ (Enterprise Schema)
* `departments (dept_id, dept_name, location, budget)`
* `employees (emp_id, emp_name, dept_id, salary, hire_date, status, manager_id)`
* `projects (proj_id, proj_name, start_date, end_date, budget)`
* `project_assignments (emp_id, proj_id, hours_worked, role)`
* `salaries_history (id, emp_id, change_date, old_salary, new_salary)`

---

### [ระดับ 1: ปูพื้นฐานแม่นยำ (Easy)]

#### ข้อ 1: การเลือกและเปลี่ยนชื่อคอลัมน์ (Projection & Calculation)
* **โจทย์:** จงแสดงชื่อพนักงาน เงินเดือนปัจจุบัน และเงินเดือนหลังหักภาษี 5% โดยตั้งชื่อคอลัมน์ว่า `net_salary` ของพนักงานทุกคน
```sql
SELECT 
    emp_name, 
    salary, 
    (salary * 0.95) AS net_salary
FROM employees;
```

#### ข้อ 2: การกรองด้วยช่วงข้อมูล (Range Filtering)
* **โจทย์:** จงหารายชื่อพนักงานที่มีเงินเดือนระหว่าง 25,000 ถึง 45,000 บาท และเริ่มงานในปี 2022
```sql
SELECT emp_name, salary, hire_date
FROM employees
WHERE salary BETWEEN 25000 AND 45000
  AND hire_date >= '2022-01-01' AND hire_date <= '2022-12-31';
```

#### ข้อ 3: การตรวจจับค่าว่าง (Handling NULLs)
* **โจทย์:** จงแสดงชื่อพนักงานทุกคนที่ **ไม่มีหัวหน้างาน** (ผู้บริหารระดับสูงสุด)
```sql
SELECT emp_id, emp_name
FROM employees
WHERE manager_id IS NULL;
```

#### ข้อ 4: การค้นหาด้วย Pattern (Wildcard Search)
* **โจทย์:** จงหาพนักงานที่มีชื่อขึ้นต้นด้วยตัวอักษร 'S' และสังกัดแผนกใดก็ได้ที่ตั้งอยู่ในเมือง 'Bangkok'
```sql
SELECT e.emp_name, d.dept_name, d.location
FROM employees e
JOIN departments d ON e.dept_id = d.dept_id
WHERE e.emp_name LIKE 'S%'
  AND d.location = 'Bangkok';
```

---

### [ระดับ 2: ตรรกะวันเวลาและ Aggregation (Medium)]

#### ข้อ 5: คำนวณอายุงานที่แม่นยำและกรองเงื่อนไข
* **โจทย์:** จงแสดงรหัสพนักงาน ชื่อ วันที่เริ่มงาน และอายุงานเต็มปี (คำนวณ ณ ปัจจุบัน) เฉพาะพนักงานที่มีอายุงานตั้งแต่ 3 ปีขึ้นไป
```sql
SELECT 
    emp_id, 
    emp_name, 
    hire_date, 
    TIMESTAMPDIFF(YEAR, hire_date, CURDATE()) AS years_of_service
FROM employees
WHERE TIMESTAMPDIFF(YEAR, hire_date, CURDATE()) >= 3
ORDER BY years_of_service DESC;
```

#### ข้อ 6: การหาผลรวมและกรองกลุ่มด้วย HAVING
* **โจทย์:** จงหาชื่อแผนก และยอดเงินเดือนรวมของแต่ละแผนก เฉพาะแผนกที่มีพนักงานมากกว่า 3 คน และมียอดรวมเงินเดือนเกิน 150,000 บาท
```sql
SELECT 
    d.dept_name, 
    COUNT(e.emp_id) AS total_employees, 
    SUM(e.salary) AS total_payroll
FROM departments d
JOIN employees e ON d.dept_id = e.dept_id
GROUP BY d.dept_id, d.dept_name
HAVING COUNT(e.emp_id) > 3 
   AND SUM(e.salary) > 150000;
```

#### ข้อ 7: หาข้อมูลที่ไม่มีคู่ (Anti-Join Pattern)
* **โจทย์:** จงหารายชื่อแผนกทั้งหมดที่ **ยังไม่มีพนักงานสังกัดอยู่เลยแม้แต่คนเดียว**
```sql
SELECT d.dept_id, d.dept_name
FROM departments d
LEFT JOIN employees e ON d.dept_id = e.dept_id
WHERE e.emp_id IS NULL;
```
*💡 คำอธิบาย:* เมื่อทำ LEFT JOIN แผนกที่ไม่มีพนักงาน ค่า `e.emp_id` จะเป็น `NULL` เมื่อใส่ `WHERE e.emp_id IS NULL` จึงได้แผนกที่ว่างเปล่าทันที!

#### ข้อ 8: การแปลงเงื่อนไขด้วย CASE WHEN (Conditional Aggregation)
* **โจทย์:** จงสรุปจำนวนพนักงานในแต่ละแผนก โดยแยกนับเป็น 2 คอลัมน์: พนักงานประจำ (`status = 'ACTIVE'`) และพนักงานทดลองงาน (`status = 'PROBATION'`)
```sql
SELECT 
    d.dept_name,
    COUNT(CASE WHEN e.status = 'ACTIVE' THEN 1 END) AS active_count,
    COUNT(CASE WHEN e.status = 'PROBATION' THEN 1 END) AS probation_count,
    COUNT(e.emp_id) AS total_count
FROM departments d
LEFT JOIN employees e ON d.dept_id = e.dept_id
GROUP BY d.dept_id, d.dept_name;
```

---

### [ระดับ 3: การเชื่อมโยงหลายตารางและ Subquery (Hard)]

#### ข้อ 9: พนักงานกับหัวหน้างาน (Self Join)
* **โจทย์:** จงแสดงรายชื่อพนักงาน พร้อมแสดง **ชื่อหัวหน้างานของตนเอง** (หากใครไม่มีหัวหน้าให้แสดง 'Top Executive')
```sql
SELECT 
    e.emp_name AS employee_name,
    COALESCE(m.emp_name, 'Top Executive') AS manager_name
FROM employees e
LEFT JOIN employees m ON e.manager_id = m.emp_id;
```

#### ข้อ 10: หาพนักงานที่ได้เงินเดือนสูงกว่าค่าเฉลี่ยของแผนกตนเอง (Correlated Subquery)
* **โจทย์:** จงหารายชื่อพนักงานที่ได้รับเงินเดือน **สูงกว่าค่าเฉลี่ยเงินเดือนของแผนกที่ตนเองสังกัดอยู่**
```sql
SELECT e.emp_id, e.emp_name, e.department, e.salary
FROM employees e
WHERE e.salary > (
    SELECT AVG(sub.salary)
    FROM employees sub
    WHERE sub.dept_id = e.dept_id
);
```

#### ข้อ 11: หาโครงการที่พนักงานทุกคนในแผนก IT มีส่วนร่วม (Division Problem in SQL)
* **โจทย์:** จงหารหัสและชื่อโครงการที่พนักงานแผนก IT **ทุกคน** ได้รับมอบหมายให้ทำ
```sql
SELECT p.proj_id, p.proj_name
FROM projects p
WHERE NOT EXISTS (
    -- หาพนักงาน IT ที่ "ไม่ได้" ถูกมอบหมายในโปรเจกต์นี้
    SELECT e.emp_id 
    FROM employees e
    JOIN departments d ON e.dept_id = d.dept_id
    WHERE d.dept_name = 'IT'
      AND NOT EXISTS (
          SELECT 1 
          FROM project_assignments pa
          WHERE pa.proj_id = p.proj_id 
            AND pa.emp_id = e.emp_id
      )
);
```

#### ข้อ 12: การหาเงินเดือนสูงสุดอันดับที่ 2 (Second Highest Salary)
* **โจทย์:** จงหาค่าเงินเดือนที่สูงที่สุดเป็นอันดับที่ 2 ของบริษัท (โดยไม่ใช้ Window Function และต้องรองรับกรณีเงินเดือนอันดับ 1 ซ้ำกันหลายคน)
```sql
SELECT MAX(salary) AS second_highest_salary
FROM employees
WHERE salary < (
    SELECT MAX(salary) 
    FROM employees
);
```

---

### [ระดับ 4: Window Functions & Advanced Analytics (Expert)]

#### ข้อ 13: หาพนักงานที่เงินเดือนสูงที่สุด Top 3 ของแต่ละแผนก
* **โจทย์:** จงแสดงชื่อแผนก ชื่อพนักงาน และเงินเดือน ของคนที่ได้เงินเดือนติดอันดับ Top 3 ของแต่ละแผนก
```sql
WITH RankedSalaries AS (
    SELECT 
        d.dept_name,
        e.emp_name,
        e.salary,
        DENSE_RANK() OVER (
            PARTITION BY e.dept_id 
            ORDER BY e.salary DESC
        ) AS salary_rank
    FROM employees e
    JOIN departments d ON e.dept_id = d.dept_id
)
SELECT dept_name, emp_name, salary, salary_rank
FROM RankedSalaries
WHERE salary_rank <= 3
ORDER BY dept_name, salary_rank;
```

#### ข้อ 14: คำนวณผลรวมสะสม (Running Total)
* **โจทย์:** จงแสดงประวัติการขึ้นเงินเดือนของพนักงานรหัส 101 พร้อมแสดงยอดสะสมของการปรับเงินเดือน (`cumulative_increase`) เรียงตามวันที่
```sql
SELECT 
    emp_id,
    change_date,
    (new_salary - old_salary) AS salary_increment,
    SUM(new_salary - old_salary) OVER (
        PARTITION BY emp_id 
        ORDER BY change_date ASC
    ) AS cumulative_increase
FROM salaries_history
WHERE emp_id = 101;
```

#### ข้อ 15: คำนวณผลต่างเทียบกับแถวก่อนหน้าด้วย LAG()
* **โจทย์:** ในระบบจดมิเตอร์น้ำ จงเขียน Query ดึงเลขมิเตอร์ครั้งก่อนหน้า (`previous_reading`) อัตโนมัติจากตารางประวัติ โดยไม่ต้องพึ่งการป้อนมือ
```sql
SELECT 
    customer_id,
    reading_date,
    current_reading,
    LAG(current_reading, 1, 0) OVER (
        PARTITION BY customer_id 
        ORDER BY reading_date ASC
    ) AS auto_previous_reading,
    (current_reading - LAG(current_reading, 1, 0) OVER (
        PARTITION BY customer_id 
        ORDER BY reading_date ASC
    )) AS calculated_units
FROM meter_readings;
```

#### ข้อ 16: การหาช่วงเวลาที่ไม่มีกิจกรรม (Gaps and Islands Problem ยอดฮิต)
* **โจทย์:** จงหาพนักงานที่ไม่มีการบันทึกชั่วโมงการทำงาน (`hours_worked`) ในโปรเจกต์ใดๆ เลยตลอดช่วง 60 วันที่ผ่านมา
```sql
SELECT e.emp_id, e.emp_name
FROM employees e
WHERE NOT EXISTS (
    SELECT 1 
    FROM project_assignments pa
    JOIN projects p ON pa.proj_id = p.proj_id
    WHERE pa.emp_id = e.emp_id
      AND p.start_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
);
```

---

### [ระดับ 5: Boss Level — ข้อสอบคัดเกียรตินิยมและสัมภาษณ์ Senior]

#### ข้อ 17: การหาอัตราการเติบโตเดือนต่อเดือน (MoM Growth Rate)
* **โจทย์:** จงคำนวณยอดรวมรายรับในแต่ละเดือน พร้อมคำนวณร้อยละการเติบโตเมื่อเทียบกับเดือนก่อนหน้า (`MoM_Growth_Pct`)
```sql
WITH MonthlyRevenue AS (
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') AS rev_month,
        SUM(amount_paid) AS total_revenue
    FROM receipts
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
),
GrowthCalc AS (
    SELECT 
        rev_month,
        total_revenue,
        LAG(total_revenue) OVER (ORDER BY rev_month) AS prev_month_revenue
    FROM MonthlyRevenue
)
SELECT 
    rev_month,
    total_revenue,
    prev_month_revenue,
    ROUND(((total_revenue - prev_month_revenue) / prev_month_revenue) * 100, 2) AS mom_growth_pct
FROM GrowthCalc;
```

#### ข้อ 18: การ Pivot ข้อมูลจากแถวเป็นคอลัมน์ (Dynamic-style Cross-Tab)
* **โจทย์:** จงสรุปค่าใช้จ่ายแยกตามสถานที่ โดยให้ชื่อสถานที่ (Bangkok, Chiang Mai, Phuket) กลายเป็นหัวคอลัมน์แนวนอนในแต่ละปี
```sql
SELECT 
    YEAR(exp_date) AS exp_year,
    SUM(CASE WHEN location = 'Bangkok' THEN amount ELSE 0 END) AS bkk_expense,
    SUM(CASE WHEN location = 'Chiang Mai' THEN amount ELSE 0 END) AS cnx_expense,
    SUM(CASE WHEN location = 'Phuket' THEN amount ELSE 0 END) AS hkt_expense,
    SUM(amount) AS grand_total
FROM expenses
GROUP BY YEAR(exp_date)
ORDER BY exp_year DESC;
```

#### ข้อ 19: ลบข้อมูลที่ซ้ำกันโดยเก็บแถวที่มี ID ล่าสุดไว้ (De-duplication Delete)
* **โจทย์:** ตาราง `logs` มีข้อมูลซ้ำซ้อนกันในคู่ `(user_id, ip_address)` จงเขียนคำสั่ง `DELETE` เพื่อลบแถวที่ซ้ำ โดยเก็บแถวที่มี `id` สูงสุดไว้เพียงแถวเดียว
```sql
DELETE l1 
FROM user_logs l1
JOIN user_logs l2 ON l1.user_id = l2.user_id 
                 AND l1.ip_address = l2.ip_address 
                 AND l1.id < l2.id;
```
*💡 คำอธิบาย:* เรา JOIN ตารางตัวเองด้วยคู่ฟิลด์ที่ซ้ำกัน แล้วระบุ `l1.id < l2.id` ซึ่งหมายความว่า `l1` จะเป็นแถวที่มี id น้อยกว่า (แถวเก่า) เมื่อสั่ง `DELETE l1` แถวเก่าที่ซ้ำทั้งหมดจะถูกลบเกลี้ยง เหลือเฉพาะแถวที่มี id สูงสุด!

#### ข้อ 20: Hierarchical Query — คำนวณพนักงานใต้บังคับบัญชาทั้งสายงาน (Recursive CTE)
* **โจทย์:** จงแสดงสายการบังคับบัญชาทั้งหมด เริ่มต้นจาก CEO (รหัส `emp_id = 1`) ลงไปจนถึงลูกน้องระดับล่างสุด พร้อมแสดงระดับความลึก (`level`)
```sql
WITH RECURSIVE OrgChart AS (
    -- Anchor Member: ผู้บริหารสูงสุด
    SELECT 
        emp_id, 
        emp_name, 
        manager_id, 
        1 AS org_level,
        CAST(emp_name AS CHAR(1000)) AS hierarchy_path
    FROM employees
    WHERE emp_id = 1

    UNION ALL

    -- Recursive Member: ดึงลูกน้องของแต่ละคนลงไปเรื่อยๆ
    SELECT 
        e.emp_id, 
        e.emp_name, 
        e.manager_id, 
        o.org_level + 1,
        CONCAT(o.hierarchy_path, ' ➔ ', e.emp_name)
    FROM employees e
    JOIN OrgChart o ON e.manager_id = o.emp_id
)
SELECT emp_id, emp_name, org_level, hierarchy_path
FROM OrgChart
ORDER BY org_level, emp_id;
```

---

# 🏆 สรุป Checklist สู่ความเป็น SQL Master
1. **ท่องจำ Execution Order:** `FROM` $\rightarrow$ `WHERE` $\rightarrow$ `GROUP BY` $\rightarrow$ `HAVING` $\rightarrow$ `SELECT` $\rightarrow$ `ORDER BY`
2. **อย่าลืมวงเล็บ:** เมื่อมี `AND` กับ `OR` อยู่ด้วยกัน
3. **อย่าเทียบด้วย `= NULL`:** ใช้ `IS NULL` หรือ `IS NOT NULL` เท่านั้น
4. **คำนวณอายุงานให้โปร:** ใช้ `TIMESTAMPDIFF(YEAR, hire_date, CURDATE())`
5. **ความต่างของ WHERE vs HAVING:** WHERE กรองก่อนรวมแถว, HAVING กรองหลัง GROUP BY
6. **SARGable:** อย่าเอา Function ไปครอบคอลัมน์ใน WHERE หากต้องการความเร็ว

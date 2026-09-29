# คู่มือการออกแบบและทดสอบ Prototype ด้วย Axure RP 11
## โครงการระบบบริหารจัดการการประปาหมู่บ้านวังยาง (Wang Yang Village Water Supply System)

เอกสารฉบับนี้จัดทำขึ้นเพื่อใช้สำหรับนำเสนอการออกแบบ (UX/UI Design) และการทดสอบระบบต้นแบบ (Test Prototype) ต่ออาจารย์ผู้สอน โดยจำลองโครงสร้างและตรรกะการทำงานตามมาตรฐาน **Axure RP 11** ครบทุกมิติ

---

## 📍 1. ตำแหน่งไฟล์สำหรับเปิดใช้งานบนเครื่อง

| รายการ | ตำแหน่งไฟล์ / URL | วิธีการเปิดใช้งาน |
|---|---|---|
| **ไฟล์โปรเจกต์ Axure RP 11** | `D:\Games\Plumber\axure\Plumber_v4.rp` (เวอร์ชันล่าสุด v4)<br>`D:\Games\Plumber\prototype\Plumber_v4.rp` | ดับเบิลคลิกไฟล์เพื่อเปิดในโปรแกรม **Axure RP 11** ได้ทันที |
| **ตัวเปิดโปรแกรม Axure RP 11** | `D:\Games\AXURE\AxureRP11.exe` | ตัวโปรแกรม Axure RP 11 ที่ติดตั้งอยู่ในเครื่อง |
| **Interactive Test Prototype (Local Web Preview)** | [http://localhost/plumber/prototype/](http://localhost/plumber/prototype/)<br>(หรือเปิด `D:\Games\Plumber\prototype\start.html`) | เปิดในเบราว์เซอร์เพื่อทดสอบคลิกเล่น Prototype พร้อมแถบเมนู Axure Player |
| **ระบบจริงที่เชื่อมฐานข้อมูล MySQL (XAMPP)** | [http://localhost/plumber/](http://localhost/plumber/) | ใช้งานระบบจริงที่มีการบันทึกฐานข้อมูล MySQL |

---

## 🗂️ 2. โครงสร้างผังหน้าจอ (Sitemap & Page Hierarchy ตาม 4 บทบาทหลัก)

ในโปรแกรม **Axure RP 11** ให้กำหนดโครงสร้างในแถบ **Pages** ฝั่งซ้ายดังนี้:

```text
📁 Wang Yang Water Supply System (Axure RP 11 - v4)
├── 📁 01_User_Public (ผู้ใช้ทั่วไป / ประชาชน)
│   ├── 📄 01_Home_Portal (หน้าแรกและบริการสาธารณะ)
│   ├── 📄 02_Citizen_Check_Bill (ตรวจสอบค่าน้ำออนไลน์ & PromptPay QR)
│   └── 📄 03_Citizen_Service_Tickets (แจ้งท่อแตกรั่ว & ยื่นคำร้อง)
├── 📁 02_Member_Portal (สมาชิกผู้ใช้น้ำ)
│   └── 📄 04_Member_Personal_Portal (แดชบอร์ดสมาชิก & บิลส่วนตัว WY-001)
├── 📁 03_Staff_Operations (เจ้าหน้าที่การประปา)
│   ├── 📄 05_Field_Meter_Reading_P17 (สมุดจดมิเตอร์ภาคสนาม แบบ ป.17)
│   ├── 📄 06_Finance_Billing_Receipt (ตัดรับชำระเงินค่าน้ำ & ใบเสร็จมาตรฐาน)
│   ├── 📄 07_Finance_Arrears_Ledger (ทะเบียนหนี้ค้างชำระ & ทบยอด)
│   ├── 📄 08_Finance_Voucher_Disbursement (ฎีกาเบิกจ่าย & คิด 10% ยอดจัดเก็บจริง)
│   └── 📄 09_Members_Registry (ทะเบียนสมาชิกผู้ใช้น้ำ)
└── 📁 04_Admin_Executive (ผู้ดูแลระบบ / คณะกรรมการ)
    ├── 📄 10_Master_Admin_Dashboard (ศูนย์ควบคุมระบบรวม & สรุปภาพรวม)
    └── 📄 11_Executive_Financial_Report_GC3 (รายงานงบการเงิน แบบ กค.3)
```

---

## 🧩 3. การออกแบบวิดเจ็ตและคอมโพเนนต์หลักใน Axure RP 11

### 3.1 Masters / Components ส่วนกลาง (Re-usable Elements)
1. **Master: `Global_Sidebar`**
   - **ขนาด**: กว้าง 260px, สูง 100% (Fit to viewport)
   - **สไตล์**: Background `#0f172a`, Text `#cbd5e1`, Active Node `#0284c7`
   - **Interaction**: ใช้เงื่อนไข Conditional Logic อ้างอิงตัวแปร Global Variable `[[ varRole ]]` เพื่อซ่อน/แสดงเมนูตาม 4 บทบาทหลัก:
     - `If varRole equals "user"` -> แสดงเฉพาะเมนูประชาชน (01-03)
     - `If varRole equals "member"` -> แสดงเมนูบิลส่วนตัวและคำร้อง (04)
     - `If varRole equals "staff"` -> แสดงงานปฏิบัติการเจ้าหน้าที่ (จดมิเตอร์, การเงิน, ทะเบียนหนี้, ฎีกาเบิกจ่าย 05-09)
     - `If varRole equals "admin"` -> แสดงครบทุกเมนูบริหารและปฏิบัติการ (01-11)

2. **Master: `Global_Topbar`**
   - **ขนาด**: กว้าง 100%, สูง 70px
   - **ฝั่งซ้าย**: Page Title (`Prompt Bold 20px`), Subtitle (`Sarabun 13.5px`)
   - **ฝั่งขวา**: User Profile Chip และปุ่ม `🔐 เข้าสู่ระบบ` หรือปุ่มสีแดง `🚪 ออกจากระบบ`

### 3.2 Dynamic Panels & State Management
1. **Dynamic Panel: `Login_Modal_Panel`** (ซ่อนไว้เป็น Default และเรียกขึ้นมาแบบ Lightbox)
   - **State 1 (`State_Member`)**: ฟอร์มล็อกอินสำหรับสมาชิกผู้ใช้น้ำ (กรอกรหัส `WY-001` หรือเบอร์โทร)
   - **State 2 (`State_Staff`)**: ฟอร์มล็อกอินสำหรับเจ้าหน้าที่ (กรอก Username `admin`, `finance`, `reader` และ Password)
   - **ปุ่มสลับแท็บ**: `OnClick` -> `Set Panel State to State_Member / State_Staff`

2. **Dynamic Panel: `Module_Tabs_Container`** (สำหรับหน้าการเงิน และหน้าบริหาร)
   - สลับระหว่างแท็บย่อย เช่น `Tab_Payments`, `Tab_Arrears`, `Tab_Vouchers` โดยไม่ต้องเปลี่ยนหน้าใหม่

---

## 🧮 4. ชุดข้อมูล Dataset และสูตรคำนวณใน Repeater Widget

ใน Axure RP 11 หน้า **สมุดจดมิเตอร์ (ป.17)** และ **งานออกใบเสร็จ** ใช้ **Repeater Widget** ในการจำลองตารางข้อมูล:

### 4.1 คอลัมน์ในตาราง Repeater (`tbl_readings`):
- `cust_code`: รหัสผู้ใช้น้ำ (เช่น `WY-001`, `WY-002`)
- `name`: ชื่อ-นามสกุล (เช่น `นายสมชาย ใจดี`)
- `house_no`: บ้านเลขที่ (เช่น `12 หมู่ 3`)
- `prev_reading`: เลขมิเตอร์ครั้งก่อน (เช่น `145.0`)
- `curr_reading`: เลขมิเตอร์ครั้งหลัง (เช่น `163.0`)
- `rate_per_unit`: อัตราค่าน้ำ (เช่น `7.00`)
- `meter_fee`: ค่าบำรุงรักษามิเตอร์ (เช่น `10.00`)
- `arrears`: ยอดค้างเก่า (เช่น `0.00`)

### 4.2 สูตรคำนวณที่ตั้งค่าใน Interaction (Events):
- **คำนวณจำนวนหน่วยใช้น้ำ**:
  ```text
  Set Text on lbl_units to [[ Item.curr_reading - Item.prev_reading ]]
  ```
- **คำนวณยอดค่าน้ำตามหน่วยจริง**:
  ```text
  Set Text on lbl_water_charge to [[ (Item.curr_reading - Item.prev_reading) * Item.rate_per_unit ]]
  ```
- **คำนวณยอดรวมสุทธิทั้งสิ้น (Net Grand Total)**:
  ```text
  Set Text on lbl_grand_total to [[ ((Item.curr_reading - Item.prev_reading) * Item.rate_per_unit) + Item.meter_fee + Item.arrears ]]
  ```
- **คำนวณค่าตอบแทนคนเก็บค่าน้ำ 10% (ในหน้าฎีกาเบิกจ่าย)**:
  ```text
  Set Text on lbl_fee_10pct to [[ TotalCollectedAmount * 0.10 ]]
  ```

---

## 🧪 5. การทดสอบ Prototype (Test Cases สำหรับพรีเซนต์ให้อาจารย์ดู)

เมื่อเปิดตัวทดสอบ Prototype ที่ **[http://localhost/plumber/prototype/](http://localhost/plumber/prototype/)**:

1. **Test Case 1: การจำลองมุมมองอุปกรณ์ (Responsive Views)**
   - คลิกปุ่ม **Desktop (1440px)**, **Tablet (820px)**, **Mobile (390px)** ที่แถบด้านบน
   - *สิ่งที่อาจารย์จะเห็น*: หน้าจอปรับสเกลขนาดอุปกรณ์พร้อม Device Frame อย่างสมจริง

2. **Test Case 2: การตรวจสอบค่าน้ำออนไลน์ของประชาชน (Citizen Self-Service)**
   - ที่หน้าแรก กรอกรหัส `WY-001` หรือ `12` ในช่องค้นหา
   - *สิ่งที่อาจารย์จะเห็น*: บิลค่าน้ำแสดงขึ้นมาทันที พร้อมคำนวณหน่วยใช้ และแสดง **PromptPay QR Code** ตามยอดเงินจริง 136.00 บาท สามารถกดดูใบแจ้งหนี้เพื่อสั่งพิมพ์ได้

3. **Test Case 3: การล็อกอินสลับสิทธิ์ (RBAC)**
   - คลิกปุ่ม `🔐 เข้าสู่ระบบ` ที่มุมบนขวา
   - ลองล็อกอินเป็น **สมาชิก (`WY-001`)** -> เมนู Sidebar จะแสดงเฉพาะบริการของสมาชิก และหน้าแรกมีแบนเนอร์ต้อนรับส่วนตัว
   - ลองล็อกอินเป็น **ผู้จดมิเตอร์ (`reader`)** -> เมนู Sidebar จะเหลือเฉพาะหน้าสมุดจดมิเตอร์ ป.17
   - ลองล็อกอินเป็น **การเงิน (`finance`)** -> เมนู Sidebar จะแสดงงานตัดรับชำระและฎีกาเบิกจ่าย
   - ลองล็อกอินเป็น **แอดมิน (`admin`)** -> เข้าถึงได้ทุกฟังก์ชันงานและรายงาน กค.3

4. **Test Case 4: สมุดจดมิเตอร์น้ำภาคสนาม ป.17 (Field Reader Engine)**
   - เปิดหน้า `05. สมุดจดมิเตอร์น้ำ (ป.17)`
   - ลองแก้ตัวเลขเลขครั้งหลังในตาราง
   - *สิ่งที่อาจารย์จะเห็น*: ปริมาณน้ำและยอดเงินรวมคำนวณใหม่แบบเรียลไทม์ทันที และหากใช้น้ำเกิน 40 ลบ.ม. จะมีป้ายเตือนสีแดง "ใช้น้ำสูงผิดปกติ" ขึ้นเตือนช่างหน้างาน

5. **Test Case 5: ฎีกาเบิกจ่ายและงบการเงิน กค.3 (Finance & Executive)**
   - เปิดหน้า `08. ฎีกาเบิกจ่าย` -> ตรวจสอบการคิดค่าตอบแทนคนเก็บค่าน้ำ **10% จากยอดที่จัดเก็บได้จริง**
   - เปิดหน้า `09. งบการเงิน กค.3` -> ตรวจสอบการแยกกระเป๋าเงินสดในมือสำรองจ่าย (5,000 บาท) และเงินฝากธนาคาร พร้อมเกจวัดน้ำสูญเสียในระบบ (NRW)

---

## 💡 สรุปการเตรียมตัวส่งงานอาจารย์
- **ส่งไฟล์ดีไซน์**: แนบไฟล์ `WangYang_Water_Supply_System.rp` (หรือ `Plumber_WangYang_Water.rp`)
- **สาธิตการทำงาน**: เปิด [http://localhost/plumber/prototype/](http://localhost/plumber/prototype/) ให้อาจารย์ทดสอบคลิกเล่นได้แบบ Interactive เต็มรูปแบบ

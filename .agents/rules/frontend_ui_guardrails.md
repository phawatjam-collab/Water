---
trigger: always_on
---

# Frontend UI & Search Component Guardrails

1. **ห้ามใช้ `display: flex !important` หรือ `display: block !important` ในคลาส Dropdown/Modal ลอยตัว:**
   - เมื่อสร้าง Dropdown, Tooltip หรือ Modal ที่ต้องเปิด/ปิดด้วย JavaScript ห้ามใส่ `display: [flex|block] !important;` ลงในคลาสหลักเด็ดขาด เพราะจะไปหักล้าง inline `style.display = 'none'` ของ JavaScript
   - ต้องใช้ Class สลับสถานะ เช่น `.search-autocomplete-dropdown.active` หรือใส่ `:empty { display: none !important; }` กำกับไว้เสมอเพื่อป้องกันกล่องเปล่าลอยค้าง

2. **ห้ามใส่ไอคอนซ้ำซ้อนทั้งใน DOM Decorator และ Placeholder:**
   - หากใน Input มีการวางไอคอนนำหน้า (Leading Icon เช่น `search-input-icon`) ห้ามใส่ Emoji ตัวเดียวกันซ้ำลงในข้อความ `placeholder` เพราะจะทำให้แสดงผลเบิ้ลสองตัว

3. **รักษารูปแบบปุ่มและโครงสร้างหลักเดิมไว้เสมอ:**
   - เมื่อได้รับการร้องขอให้ปรับปรุงความลื่นไหล (Smoothness) หรือเปลี่ยน Key หลักในการค้นหา ต้องคงปุ่ม Action เดิม (เช่น ปุ่มค้นหา) ไว้เสมอ เว้นแต่ผู้ใช้จะระบุให้ลบ Element ปุ่มนั้นออกอย่างเจาะจง

/**
 * โมดูลจัดการฐานข้อมูล SQLite (SQLite WASM via sql.js)
 * Village Water Supply Database Manager
 */

const fs = require('fs');
const path = require('path');
const initSqlJs = require('sql.js');

const DB_DIR = path.join(__dirname, '..', 'data');
const DB_FILE = path.join(DB_DIR, 'wangyang_water.db');
const SCHEMA_FILE = path.join(__dirname, '..', 'schema.sql');

let dbInstance = null;

async function getDb() {
  if (dbInstance) return dbInstance;

  const SQL = await initSqlJs();

  if (!fs.existsSync(DB_DIR)) {
    fs.mkdirSync(DB_DIR, { recursive: true });
  }

  if (fs.existsSync(DB_FILE)) {
    const fileBuffer = fs.readFileSync(DB_FILE);
    dbInstance = new SQL.Database(fileBuffer);
    console.log('✅ โหลดฐานข้อมูล SQLite จากไฟล์:', DB_FILE);
  } else {
    dbInstance = new SQL.Database();
    console.log('⚡ สร้างฐานข้อมูล SQLite ใหม่จาก schema.sql');
    if (fs.existsSync(SCHEMA_FILE)) {
      const schemaSql = fs.readFileSync(SCHEMA_FILE, 'utf-8');
      dbInstance.run(schemaSql);
      saveDb(dbInstance);
      console.log('✅ กำหนดโครงสร้างตารางและ Seed Data เรียบร้อยแล้ว');
    }
  }

  return dbInstance;
}

function saveDb(db = dbInstance) {
  if (!db) return;
  const data = db.export();
  const buffer = Buffer.from(data);
  fs.writeFileSync(DB_FILE, buffer);
}

/**
 * รันคำสั่ง SQL Query แบบคืนค่ารายการแถว (Array of Objects)
 */
async function query(sql, params = []) {
  const db = await getDb();
  const stmt = db.prepare(sql);
  stmt.bind(params);

  const results = [];
  while (stmt.step()) {
    results.push(stmt.getAsObject());
  }
  stmt.free();
  return results;
}

/**
 * รันคำสั่ง SQL Query คืนค่าแถวเดียว
 */
async function get(sql, params = []) {
  const rows = await query(sql, params);
  return rows.length > 0 ? rows[0] : null;
}

/**
 * รันคำสั่ง SQL สำหรับ INSERT, UPDATE, DELETE พร้อมบันทึกลงดิสก์
 */
async function run(sql, params = []) {
  const db = await getDb();
  db.run(sql, params);
  saveDb(db);
  const lastIdRow = await get('SELECT last_insert_rowid() as id');
  return { lastInsertRowid: lastIdRow ? lastIdRow.id : 0 };
}

module.exports = {
  getDb,
  saveDb,
  query,
  get,
  run,
  DB_FILE
};

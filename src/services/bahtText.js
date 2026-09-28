/**
 * แปลงจำนวนเงินตัวเลขเป็นตัวอักษรภาษาไทย (Thai Baht Text)
 * ตัวอย่าง: 1540.50 -> หนึ่งพันห้าร้อยสี่สิบบาทห้าสิบสตางค์
 *          500.00 -> ห้าร้อยบาทถ้วน
 */
function bahtText(num) {
  if (num === null || num === undefined || isNaN(num)) return 'ศูนย์บาทถ้วน';

  num = Number(num);
  if (num === 0) return 'ศูนย์บาทถ้วน';

  const isNegative = num < 0;
  num = Math.abs(num);

  // ปัดเศษทศนิยม 2 ตำแหน่ง
  const rounded = (Math.round(num * 100) / 100).toFixed(2);
  const [integerPart, decimalPart] = rounded.split('.');

  const thaiNumbers = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
  const thaiPositions = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];

  function convertGroup(digits) {
    let result = '';
    const len = digits.length;
    for (let i = 0; i < len; i++) {
      const digit = parseInt(digits[i], 10);
      const pos = len - 1 - i;

      if (digit !== 0) {
        if (pos === 0 && digit === 1 && len > 1) {
          result += 'เอ็ด';
        } else if (pos === 1 && digit === 1) {
          result += 'สิบ';
        } else if (pos === 1 && digit === 2) {
          result += 'ยี่สิบ';
        } else {
          result += thaiNumbers[digit] + thaiPositions[pos];
        }
      }
    }
    return result;
  }

  function convertInteger(str) {
    if (parseInt(str, 10) === 0) return 'ศูนย์';
    let result = '';
    // แบ่งกลุ่มละ 6 หลัก (ล้าน) จากขวาไปซ้าย
    const groups = [];
    let s = str;
    while (s.length > 0) {
      if (s.length > 6) {
        groups.unshift(s.slice(-6));
        s = s.slice(0, -6);
      } else {
        groups.unshift(s);
        s = '';
      }
    }

    for (let g = 0; g < groups.length; g++) {
      const groupText = convertGroup(groups[g]);
      const millionsCount = groups.length - 1 - g;
      result += groupText;
      if (groupText !== '' && millionsCount > 0) {
        result += 'ล้าน'.repeat(millionsCount);
      }
    }
    return result;
  }

  let text = (isNegative ? 'ลบ' : '') + convertInteger(integerPart) + 'บาท';

  const satangVal = parseInt(decimalPart, 10);
  if (satangVal === 0) {
    text += 'ถ้วน';
  } else {
    text += convertGroup(decimalPart) + 'สตางค์';
  }

  return text;
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { bahtText };
}

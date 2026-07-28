# AMPL Fieldwork - Product Management System User Guide

**System Version:** 2.0  
**Last Updated:** July 2, 2026  
**Target Users:** Admin, Store Manager

---

## 🎯 Quick Start

### What You Can Do Now

✅ **Create Products** with professional descriptions  
✅ **Upload Product Images** with drag-and-drop  
✅ **Manage Multiple Units** with different prices  
✅ **Create Product Attributes** (Color, Size, etc.)  
✅ **Generate Product Variants** from attributes  

---

## 📱 Main Pages & What You See

### 1. Product List Page
**URL:** `/admin/products`

**You Will See:**
- Table with all products
- Search by name or SKU
- Filter by status (Active/Inactive)
- "Create New Product" button
- Edit/Delete buttons per product

**Actions:**
- Click product name to view details
- Click "Edit" to modify
- Click delete icon to remove

---

### 2. Create New Product
**URL:** `/admin/products/create`

**THREE Sections:**

#### **Left Column (2/3 of page):**

**Product Information Section:**
- **Product Name** (required) - e.g., "Amul Butter"
- **Product Code/SKU** - e.g., "AMB-100"
- **Description** - Rich text editor with formatting toolbar
  - Bold, Italic, Underline, Strikethrough
  - Lists (bullet and numbered)
  - Blockquotes, code blocks
  - Headings, links, images
  - 300px tall editor

**Units & Prices Section:**
- "Add Unit" button
- Table showing all units for this product
- Each unit has:
  - Unit Name (e.g., "Box", "Piece", "Kg")
  - Price in ₹ (Indian Rupees)
  - Default status (radio button)
  - Remove button
- Quick-add buttons for common units:
  - Piece, Box, Carton, Kg, Gram, Liter, ML, Pack, Dozen, Set, Pair, Roll, Sheet, Bag, Can, Bottle

#### **Right Column (1/3 of page):**

**Status Section:**
- Radio buttons: Active / Inactive
- Choose what appears in store

**Common Units:**
- Quick reference buttons
- Click any unit to add it instantly

**Save/Cancel Buttons:**
- "Save Product" button (primary)
- "Cancel" link

---

### 3. Edit Product
**URL:** `/admin/products/{id}/edit`

**FIVE Sections:**

#### **Section 1: Product Information** (same as create)

#### **Section 2: Product Images**
What you see:
- **Drag & Drop Zone**
  - Gray dashed border
  - Says "Drop images here or click to upload"
  - Click to open file picker
- **Upload Progress Bar** (appears during upload)
  - Shows percentage complete
- **Gallery Grid**
  - Shows 2-4 columns of images
  - Each image 130px tall
  - Hover shows buttons:
    - "Set Featured" button (if not featured)
    - "Delete" button
  - Featured image shows green badge
- **Empty State** (if no images)
  - "No images yet. Upload some to get started!"

**How to Use:**
1. Drag images from your computer onto the zone
2. OR click zone to open file picker
3. Select multiple images
4. Progress bar shows upload status
5. Once uploaded, images appear in gallery
6. Click "Set Featured" on an image to make it the main product image
7. Click "Delete" to remove an image
8. Images update immediately (no form submission needed)

#### **Section 3: Units & Prices** (same as create)

#### **Section 4: Attributes & Variants** ⭐ NEW
What you see:
- **Link:** "⚙ Manage Attributes" (top right)
  - Click to manage all attributes in system

**Product Attributes Box:**
- Label: "Product Attributes"
- Checklist of all available attributes
- Shows count of values for each (e.g., "Color (5 values)")
- Examples:
  - ☐ Color (5 values)
  - ☐ Size (4 values)
  - ☐ Material (3 values)
- Scrollable list (max 48px height)
- Check/uncheck to apply to this product

**Existing Variants Section:** (if product has variants)
- Table showing all variants
- Columns:
  - Attributes (color-coded badges, e.g., "Color: Red", "Size: Small")
  - SKU (e.g., "TSHIRT-RED-S")
  - Price (₹100)
  - Stock (quantity)
  - Actions (Edit, Delete buttons)
- No variants message if empty

**Generate Variants Button:**
- Blue box with bulb icon
- Text: "💡 Tip: After selecting attributes above, you can bulk-generate all possible variant combinations."
- Button: "🔄 Generate Variants"
- Click after selecting attributes to create all combinations

#### **Section 5: Status & Danger Zone**
- Status radio (Active/Inactive)
- Delete button (with confirmation)

**Action Buttons:**
- "💾 Update Product" (green)
- "Cancel" link
- "🗑 Delete Product" (red, in Danger Zone)

---

### 4. Manage Attributes
**URL:** `/admin/product-attributes`

**You Will See:**
- List of all attribute types (Color, Size, Brand, etc.)
- Search bar
- Status filter (Active/Inactive)
- "Create New Attribute" button
- Each attribute shows:
  - Name
  - Slug (URL-safe version)
  - Number of values (e.g., "5 values")
  - Number of products using it (e.g., "12 products")
  - Edit/Delete buttons

**Create New Attribute:**
1. Click "Create New Attribute" button
2. Fill in:
   - **Attribute Name** (required) - e.g., "Color"
   - **Slug** (auto-filled) - e.g., "color"
   - **Description** (optional)
   - **Input Type** - Choose from dropdown:
     - Text Input
     - Dropdown
     - Multi-Select
     - Color Picker
     - Date Picker
   - **Status** - Checkbox "Active"
3. Click "Create Attribute"

**Edit Attribute:**
1. Click "Edit" on an attribute
2. On edit page, see all values in a table
3. Click "Add Value" button
4. Enter:
   - Value name (e.g., "Red")
   - Color code (if color picker, e.g., "#FF0000")
5. Click "Add"
6. Values appear in table
7. Click "Delete" on any value to remove

---

## 🎨 Feature Walkthrough

### Example: Creating a T-Shirt Product with Variants

**Step 1: Create Product**
1. Go to `/admin/products/create`
2. Enter:
   - Name: "Classic T-Shirt"
   - SKU: "TSHIRT-001"
   - Description: "100% cotton comfortable t-shirt" (using rich text editor)
   - Status: Active
3. Add units:
   - Piece @ ₹499 (default)
   - Pack of 5 @ ₹2,250

**Step 2: Upload Images**
1. On edit page, drag 3 images to upload zone
2. First image automatically featured
3. All images visible in gallery

**Step 3: Set Up Attributes** (if not done before)
1. Go to `/admin/product-attributes/create`
2. Create "Color" attribute with values: Red, Blue, Green
3. Create "Size" attribute with values: S, M, L, XL

**Step 4: Add Variants**
1. Back on product edit page
2. Check: ☑ Color, ☑ Size
3. Click "Generate Variants" button
4. System creates 12 variants:
   - Red + S, Red + M, Red + L, Red + XL
   - Blue + S, Blue + M, Blue + L, Blue + XL
   - Green + S, Green + M, Green + L, Green + XL

**Step 5: Set Variant Details**
1. In "Existing Variants" table, edit each variant:
   - SKU: TSHIRT-001-RED-S (auto-suggested)
   - Price: ₹499 (inherited from base)
   - Stock: 50

**Step 6: Save**
1. Click "Update Product"
2. Product now has:
   - Full description
   - 3 images (1 featured)
   - 2 units with prices
   - 12 variants with stock tracking

---

## 🎯 Common Tasks

### Task 1: Add a New Product
1. `/admin/products/create`
2. Fill form (name, code, description required)
3. Add at least 1 unit with price
4. Click "Save Product"

### Task 2: Upload Product Images
1. Edit product
2. Go to "Product Images" section
3. Drag images or click to upload
4. Click "Set Featured" on main image
5. No form submission needed - images auto-save

### Task 3: Create Product with Multiple Units
1. On create/edit page
2. Click "+ Add Unit"
3. Enter unit name and price
4. Repeat for each unit
5. Click radio button to mark default
6. Save

### Task 4: Create Color Attribute
1. `/admin/product-attributes/create`
2. Name: "Color"
3. Input Type: "Color Picker"
4. Click "Create Attribute"
5. Click "Edit" to add values
6. Click "+ Add Value"
7. Enter:
   - Value: "Red"
   - Color Code: "#FF0000"
8. Repeat for other colors

### Task 5: Create Variants for a Product
1. Edit product
2. Scroll to "Attributes & Variants"
3. Check relevant attributes
4. Click "Generate Variants"
5. Table shows all combinations
6. Edit each variant as needed
7. Click "Update Product"

---

## 💡 Tips & Best Practices

### For Rich Text Descriptions
- Use headers for sections
- Bullet points for features
- Bold for important info
- Links for more details
- Don't use too many colors

### For Images
- Use high-quality images (1000x1000px recommended)
- JPG or PNG format
- Product should look clear
- Remove backgrounds if selling online
- Upload 3-5 images per product

### For Units & Pricing
- Always have at least 1 unit
- Mark most common unit as default
- Use consistent unit names
- Include GST in price if applicable

### For Attributes
- Keep attribute names short and clear
- Use consistent naming (Color, not Colour)
- Create attribute before applying to products
- Add all values before using for variants

### For Variants
- Generate all combinations first
- Then edit each variant individually
- Add unique SKU to each variant
- Update stock quantities regularly

---

## ⚠️ Important Notes

### Data Preservation
- Editing product description auto-saves
- Images upload automatically (no form needed)
- When saving product, all data is persisted
- Deleting product is permanent

### Storage & File Limits
- Max image size: 5MB
- Supported formats: PNG, JPG, GIF, WebP
- Images stored in `/storage/app/public/products/`
- Public symlink active for image serving

### Browser Compatibility
- Works on Chrome, Firefox, Safari, Edge
- Mobile-friendly responsive design
- File drag-drop requires HTML5 browser
- Rich text editor works on all modern browsers

---

## 🔧 Troubleshooting

### Images Not Uploading
- Check file size (max 5MB)
- Check file format (PNG, JPG, GIF)
- Try different browser
- Check internet connection

### Description Text Not Saving
- Text auto-saves when you move to next field
- Look for "Compiled views cleared" message
- Refresh page and try again

### Variants Not Appearing
- Check if attributes are selected
- Click "Generate Variants" button
- Refresh page if needed

### Can't Find Product
- Use search bar to find by name or SKU
- Check status filter (might be set to Inactive)
- Clear search and try again

---

## 📞 Support

For questions or issues:
1. Check this guide first
2. Try a different browser
3. Clear browser cache
4. Contact administrator

---

**Happy product management!** 🚀

import { MaterialIcons, MaterialCommunityIcons } from "@expo/vector-icons";
import React, { useEffect, useState, useMemo } from "react";
import {
  Keyboard,
  KeyboardAvoidingView,
  Modal,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import ScreenHeader from "../components/ScreenHeader";
import { CartItem } from "./OrderScreen";
import { Product, ProductUnit } from "../api/product";

interface Props {
  visible: boolean;
  onClose: () => void;
  products: Product[];
  onAddToCart: (item: CartItem) => void;
}

export default function ProductSelectionModal({
  visible,
  onClose,
  products,
  onAddToCart,
}: Props) {
  const insets = useSafeAreaInsets();
  const [search, setSearch] = useState("");
  const [expandedProductId, setExpandedProductId] = useState<number | null>(
    null,
  );
  const [quantities, setQuantities] = useState<Record<string, number>>({});

  const filteredProducts = useMemo(() => {
    if (!search) return products;
    return products.filter(
      (p) =>
        p.name.toLowerCase().includes(search.toLowerCase()) ||
        (p.code && p.code.toLowerCase().includes(search.toLowerCase())),
    );
  }, [products, search]);

  const updateQuantity = (productId: number, unitId: number, delta: number) => {
    const key = `${productId}-${unitId}`;
    const current = quantities[key] || 0;
    const next = Math.max(0, current + delta);
    setQuantities((prev) => ({ ...prev, [key]: next }));
  };

  const handleAddAll = () => {
    let itemsAdded = 0;
    products.forEach((product) => {
      product.units.forEach((unit) => {
        const key = `${product.id}-${unit.id}`;
        const q = quantities[key];
        if (q && q > 0) {
          onAddToCart({ product, unit, quantity: q });
          itemsAdded++;
        }
      });
    });

    // Reset quantities and close
    setQuantities({});
    if (itemsAdded > 0) {
      onClose();
    }
  };

  const selectionSummary = useMemo(() => {
    let totalQty = 0;
    let totalPrice = 0;
    let totalVariants = 0;

    products.forEach((product) => {
      product.units.forEach((unit) => {
        const key = `${product.id}-${unit.id}`;
        const q = quantities[key] || 0;
        if (q > 0) {
          totalQty += q;
          totalPrice += q * (Number(unit.price) || 0);
          totalVariants++;
        }
      });
    });

    return { totalQty, totalPrice, totalVariants };
  }, [products, quantities]);

  const hasSelections = selectionSummary.totalQty > 0;

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <View style={styles.safe}>
        <ScreenHeader
          title="Select Products"
          onBack={onClose}
          bgColor="#7367F0"
        />

        <KeyboardAvoidingView
          style={{ flex: 1 }}
          behavior={Platform.OS === "ios" ? "padding" : undefined}
        >
          <View style={styles.searchContainer}>
            <MaterialIcons name="search" size={20} color="#9ca3af" />
            <TextInput
              style={styles.searchInput}
              placeholder="Search products..."
              value={search}
              onChangeText={setSearch}
            />
            {search ? (
              <TouchableOpacity onPress={() => setSearch("")}>
                <MaterialIcons name="cancel" size={20} color="#9ca3af" />
              </TouchableOpacity>
            ) : null}
          </View>

          <ScrollView
            style={{ flex: 1 }}
            contentContainerStyle={[
              styles.list,
              { paddingBottom: Math.max(insets.bottom, 14) + 90 },
            ]}
            keyboardShouldPersistTaps="handled"
            keyboardDismissMode="on-drag"
            showsVerticalScrollIndicator={false}
          >
            {filteredProducts.map((product) => {
              const isExpanded = expandedProductId === product.id;
              const productSelectedQty = product.units.reduce(
                (acc, u) => acc + (quantities[`${product.id}-${u.id}`] || 0),
                0,
              );
              const productSelectedTotal = product.units.reduce(
                (acc, u) =>
                  acc +
                  (quantities[`${product.id}-${u.id}`] || 0) *
                    (Number(u.price) || 0),
                0,
              );

              return (
                <View key={product.id} style={styles.productCard}>
                  <TouchableOpacity
                    style={styles.productHeader}
                    onPress={() =>
                      setExpandedProductId(isExpanded ? null : product.id)
                    }
                  >
                    <View style={{ flex: 1, marginRight: 8 }}>
                      <Text style={styles.productName}>{product.name}</Text>
                      <View style={{ flexDirection: "row", alignItems: "center", flexWrap: "wrap", gap: 8, marginTop: 4 }}>
                        {product.code && (
                          <Text style={styles.productCode}>
                            SKU: {product.code}
                          </Text>
                        )}
                        {productSelectedQty > 0 && (
                          <View style={styles.productSelectedBadge}>
                            <Text style={styles.productSelectedBadgeText}>
                              {productSelectedQty} selected • ₹{productSelectedTotal.toFixed(2)}
                            </Text>
                          </View>
                        )}
                      </View>
                    </View>
                    <MaterialIcons
                      name={
                        isExpanded ? "keyboard-arrow-up" : "keyboard-arrow-down"
                      }
                      size={24}
                      color="#6b7280"
                    />
                  </TouchableOpacity>

                  {isExpanded && (
                    <View style={styles.variantsContainer}>
                      {product.units.length === 0 ? (
                        <Text style={styles.noUnits}>
                          No variations available
                        </Text>
                      ) : (
                        product.units.map((unit) => {
                          const key = `${product.id}-${unit.id}`;
                          const q = quantities[key] || 0;
                          const unitPrice = Number(unit.price) || 0;
                          const lineTotal = q * unitPrice;
                          return (
                            <View key={unit.id} style={styles.variantRow}>
                              <View style={{ flex: 1, marginRight: 8 }}>
                                <Text style={styles.unitName}>
                                  {unit.unit_name}
                                </Text>
                                <View style={{ flexDirection: "row", alignItems: "center", gap: 6, marginTop: 2 }}>
                                  <Text style={styles.unitPrice}>
                                    ₹{unitPrice.toFixed(2)}
                                  </Text>
                                  {q > 0 && (
                                    <Text style={styles.variantTotalText}>
                                      • Total: ₹{lineTotal.toFixed(2)}
                                    </Text>
                                  )}
                                </View>
                              </View>
                              <View style={styles.quantityControl}>
                                <TouchableOpacity
                                  style={[
                                    styles.qBtn,
                                    q === 0 && { opacity: 0.5 },
                                  ]}
                                  onPress={() =>
                                    updateQuantity(product.id, unit.id, -1)
                                  }
                                  disabled={q === 0}
                                >
                                  <MaterialIcons
                                    name="remove"
                                    size={16}
                                    color="#374151"
                                  />
                                </TouchableOpacity>
                                <Text style={styles.qText}>{q}</Text>
                                <TouchableOpacity
                                  style={styles.qBtn}
                                  onPress={() =>
                                    updateQuantity(product.id, unit.id, 1)
                                  }
                                >
                                  <MaterialIcons
                                    name="add"
                                    size={16}
                                    color="#374151"
                                  />
                                </TouchableOpacity>
                              </View>
                            </View>
                          );
                        })
                      )}
                    </View>
                  )}
                </View>
              );
            })}
          </ScrollView>

          <View
            style={[
              styles.footer,
              { paddingBottom: Math.max(insets.bottom, 14) + 8 },
            ]}
          >
            <View style={styles.footerRow}>
              <View style={styles.footerPriceInfo}>
                <Text style={styles.footerTotalLabel}>Total Amount</Text>
                <Text style={styles.footerTotalPrice}>
                  ₹{selectionSummary.totalPrice.toFixed(2)}
                </Text>
                <Text style={styles.footerTotalQty}>
                  {selectionSummary.totalQty} units • {selectionSummary.totalVariants} items
                </Text>
              </View>

              <TouchableOpacity
                style={[
                  styles.confirmBtn,
                  !hasSelections && styles.confirmBtnDisabled,
                ]}
                disabled={!hasSelections}
                onPress={handleAddAll}
              >
                <Text style={styles.confirmBtnText}>Add to Order</Text>
              </TouchableOpacity>
            </View>
          </View>
        </KeyboardAvoidingView>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#f9fafb" },
  header: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#e5e7eb",
  },
  headerIconBtn: { padding: 8, marginLeft: -8 },
  headerTitle: {
    flex: 1,
    textAlign: "center",
    fontSize: 18,
    fontWeight: "700",
    color: "#111827",
  },
  searchContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#fff",
    margin: 16,
    paddingHorizontal: 12,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#e5e7eb",
  },
  searchInput: {
    flex: 1,
    paddingVertical: 12,
    paddingHorizontal: 8,
    fontSize: 16,
  },
  list: { paddingHorizontal: 16, paddingBottom: 100 },
  productCard: {
    backgroundColor: "#fff",
    borderRadius: 12,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "#e5e7eb",
    overflow: "hidden",
  },
  productHeader: { flexDirection: "row", alignItems: "center", padding: 16 },
  productName: { fontSize: 15, fontWeight: "bold", color: "#1f2937" },
  productCode: { fontSize: 12, color: "#6b7280", marginTop: 4 },
  variantsContainer: {
    backgroundColor: "#f9fafb",
    borderTopWidth: 1,
    borderTopColor: "#e5e7eb",
    padding: 16,
  },
  noUnits: { fontSize: 13, color: "#ef4444", fontStyle: "italic" },
  variantRow: { flexDirection: "row", alignItems: "center", marginBottom: 12 },
  unitName: { fontSize: 14, fontWeight: "600", color: "#374151" },
  unitPrice: {
    fontSize: 13,
    color: "#10B981",
    fontWeight: "bold",
    marginTop: 2,
  },
  quantityControl: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#d1d5db",
    borderRadius: 8,
  },
  qBtn: { padding: 8 },
  qText: {
    width: 30,
    textAlign: "center",
    fontSize: 15,
    fontWeight: "600",
    color: "#111827",
  },
  productSelectedBadge: {
    backgroundColor: "#EDE9FE",
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: "#DDD6FE",
  },
  productSelectedBadgeText: {
    fontSize: 11,
    color: "#7367F0",
    fontWeight: "700",
  },
  variantTotalText: {
    fontSize: 12,
    color: "#7367F0",
    fontWeight: "700",
  },
  footer: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: "#fff",
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderTopWidth: 1,
    borderTopColor: "#e5e7eb",
    elevation: 20,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: -3 },
    shadowOpacity: 0.08,
    shadowRadius: 8,
  },
  footerRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  footerPriceInfo: {
    flex: 1,
    marginRight: 16,
  },
  footerTotalLabel: {
    fontSize: 11,
    color: "#6B7280",
    fontWeight: "600",
    textTransform: "uppercase",
  },
  footerTotalPrice: {
    fontSize: 18,
    fontWeight: "800",
    color: "#7367F0",
  },
  footerTotalQty: {
    fontSize: 12,
    color: "#6B7280",
    fontWeight: "600",
    marginTop: 1,
  },
  confirmBtn: {
    backgroundColor: "#7367F0",
    paddingVertical: 13,
    paddingHorizontal: 22,
    borderRadius: 12,
    alignItems: "center",
    justifyContent: "center",
    shadowColor: "#7367F0",
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.25,
    shadowRadius: 6,
    elevation: 3,
  },
  confirmBtnDisabled: {
    opacity: 0.5,
  },
  confirmBtnText: { color: "#fff", fontSize: 15, fontWeight: "700" },
});

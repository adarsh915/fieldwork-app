import { MaterialIcons, MaterialCommunityIcons } from "@expo/vector-icons";
import React, { useEffect, useState, useMemo } from "react";
import {
  Keyboard,
  KeyboardAvoidingView,
  Modal,
  Platform,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";

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

  const hasSelections = Object.values(quantities).some((q) => q > 0);

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <SafeAreaView style={styles.safe}>
        <KeyboardAvoidingView
          style={{ flex: 1 }}
          behavior={Platform.OS === "ios" ? "padding" : "height"}
        >
          <View style={styles.header}>
            <TouchableOpacity style={styles.headerIconBtn} onPress={onClose}>
              <MaterialIcons name="close" size={24} color="#374151" />
            </TouchableOpacity>
            <Text style={styles.headerTitle}>Select Products</Text>
            <View style={{ width: 40 }} />
          </View>

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
            contentContainerStyle={[styles.list, { paddingBottom: 24 }]}
            keyboardShouldPersistTaps="handled"
            keyboardDismissMode="on-drag"
            showsVerticalScrollIndicator={false}
          >
            {filteredProducts.map((product) => {
              const isExpanded = expandedProductId === product.id;
              return (
                <View key={product.id} style={styles.productCard}>
                  <TouchableOpacity
                    style={styles.productHeader}
                    onPress={() =>
                      setExpandedProductId(isExpanded ? null : product.id)
                    }
                  >
                    <View style={{ flex: 1 }}>
                      <Text style={styles.productName}>{product.name}</Text>
                      {product.code && (
                        <Text style={styles.productCode}>
                          SKU: {product.code}
                        </Text>
                      )}
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
                          return (
                            <View key={unit.id} style={styles.variantRow}>
                              <View style={{ flex: 1 }}>
                                <Text style={styles.unitName}>
                                  {unit.unit_name}
                                </Text>
                                <Text style={styles.unitPrice}>
                                  ₹{unit.price}
                                </Text>
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

          <View style={styles.footer}>
            <TouchableOpacity
              style={[styles.confirmBtn, !hasSelections && { opacity: 0.5 }]}
              disabled={!hasSelections}
              onPress={handleAddAll}
            >
              <Text style={styles.confirmBtnText}>Add Selected to Order</Text>
            </TouchableOpacity>
          </View>
        </KeyboardAvoidingView>
      </SafeAreaView>
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
  footer: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: "#fff",
    padding: 16,
    borderTopWidth: 1,
    borderTopColor: "#e5e7eb",
    elevation: 20,
  },
  confirmBtn: {
    backgroundColor: "#10B981",
    padding: 16,
    borderRadius: 12,
    alignItems: "center",
  },
  confirmBtnText: { color: "#fff", fontSize: 16, fontWeight: "bold" },
});

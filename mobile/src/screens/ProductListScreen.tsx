import { MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useMemo, useState, useCallback } from "react";
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import ScreenHeader from "../components/ScreenHeader";
import { getProducts, type Product } from "../api/product";
import type { RootStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "ProductList">;

export default function ProductListScreen() {
  const navigation = useNavigation<Nav>();
  const [query, setQuery] = useState("");

  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useFocusEffect(
    useCallback(() => {
      let isActive = true;
      setLoading(true);
      getProducts()
        .then((res) => {
          if (isActive && res.success) {
            setProducts(res.data);
          }
        })
        .catch((err) => {
          console.error(err);
          Alert.alert("Error", "Failed to load products.");
        })
        .finally(() => {
          if (isActive) setLoading(false);
        });

      return () => {
        isActive = false;
      };
    }, []),
  );

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return products;
    return products.filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        (p.code && p.code.toLowerCase().includes(q)) ||
        (p.category && p.category.toLowerCase().includes(q)),
    );
  }, [products, query]);

  return (
    <View style={{ flex: 1, backgroundColor: "#EDEDED" }}>
      <ScreenHeader
        title="Product List"
        onBack={() => navigation.goBack()}
        rightElement={
          <TouchableOpacity style={{ width: 36, height: 36, alignItems: "center", justifyContent: "center" }}>
            <MaterialIcons name="notifications-none" size={24} color="#FFFFFF" />
          </TouchableOpacity>
        }
      />

      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.searchWrap}>
          <TextInput
            style={styles.searchInput}
            placeholder="Search products..."
            placeholderTextColor="#8E8E8E"
            value={query}
            onChangeText={setQuery}
          />
          <MaterialIcons name="search" size={22} color="#7367F0" />
        </View>

        <View style={styles.totalsCard}>
          <View style={styles.totalCol}>
            <Text style={[styles.totalValue, { color: "#f59e0b" }]}>
              {filtered.length}
            </Text>
            <Text style={[styles.totalLabel, { color: "#f59e0b" }]}>
              Total Products
            </Text>
          </View>
        </View>

        {loading ? (
          <ActivityIndicator
            size="large"
            color="#7367F0"
            style={{ marginTop: 40 }}
          />
        ) : filtered.length === 0 ? (
          <Text style={styles.emptyText}>No products found.</Text>
        ) : (
          filtered.map((product) => (
            <View key={product.id} style={styles.productCard}>
              <View style={styles.productAccent} />
              <View style={styles.productBody}>
                <Text style={styles.productTitle}>{product.name}</Text>

                <View style={styles.productRow}>
                  <Text style={styles.productLabel}>Code:</Text>
                  <Text style={styles.productValue}>{product.code || "N/A"}</Text>
                </View>

                <View style={styles.productRow}>
                  <Text style={styles.productLabel}>Category:</Text>
                  <Text style={styles.productValue}>{product.category || "N/A"}</Text>
                </View>
                
                {product.units && product.units.length > 0 && (
                  <View style={styles.unitsContainer}>
                    <Text style={styles.unitsTitle}>Units:</Text>
                    {product.units.map(unit => (
                      <View key={unit.id} style={styles.unitRow}>
                        <Text style={styles.unitName}>{unit.unit_name}</Text>
                        <Text style={styles.unitPrice}>₹{unit.price}</Text>
                      </View>
                    ))}
                  </View>
                )}
              </View>
            </View>
          ))
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: "#EDEDED",
  },
  header: {
    backgroundColor: "#7367F0",
    paddingHorizontal: 14,
    paddingVertical: 16,
    flexDirection: "row",
    alignItems: "center",
  },
  headerIconBtn: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: "center",
    justifyContent: "center",
  },
  headerTitle: {
    flex: 1,
    textAlign: "center",
    color: "#FFFFFF",
    fontSize: 18,
    fontWeight: "800",
  },
  headerRight: {
    flexDirection: "row",
    alignItems: "center",
    width: 40,
  },
  scrollContent: {
    padding: 14,
    paddingBottom: 40,
  },
  searchWrap: {
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#7367F0",
    paddingHorizontal: 14,
    height: 52,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 12,
  },
  searchInput: {
    flex: 1,
    fontSize: 16,
    fontWeight: "800",
    color: "#7367F0",
  },
  totalsCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    paddingTop: 14,
    paddingBottom: 14,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    shadowColor: "#000",
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 6,
    marginBottom: 12,
  },
  totalCol: {
    alignItems: "center",
    paddingHorizontal: 4,
  },
  totalValue: {
    fontWeight: "900",
    fontSize: 18,
  },
  totalLabel: {
    marginTop: 2,
    fontWeight: "800",
    fontSize: 12,
  },
  emptyText: {
    textAlign: "center",
    marginTop: 40,
    color: "#8e8e8e",
    fontWeight: "bold",
  },
  productCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    overflow: "hidden",
    flexDirection: "row",
    marginBottom: 12,
    shadowColor: "#000",
    shadowOpacity: 0.06,
    shadowRadius: 10,
    elevation: 4,
  },
  productAccent: {
    width: 6,
    backgroundColor: "#10B981", // green accent
  },
  productBody: {
    flex: 1,
    padding: 12,
  },
  productTitle: {
    fontWeight: "900",
    fontSize: 14,
    color: "#111827",
    marginBottom: 8,
  },
  productRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 4,
  },
  productLabel: {
    fontWeight: "700",
    fontSize: 12,
    color: "#6B7280",
    width: 65,
  },
  productValue: {
    fontWeight: "800",
    fontSize: 12,
    color: "#111827",
    flex: 1,
  },
  unitsContainer: {
    marginTop: 10,
    backgroundColor: "#F3F4F6",
    borderRadius: 8,
    padding: 8,
  },
  unitsTitle: {
    fontWeight: "800",
    fontSize: 12,
    color: "#374151",
    marginBottom: 6,
  },
  unitRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 4,
  },
  unitName: {
    fontWeight: "600",
    fontSize: 12,
    color: "#4B5563",
  },
  unitPrice: {
    fontWeight: "800",
    fontSize: 12,
    color: "#10B981",
  }
});

import Decimal from 'decimal.js';

// Configure Decimal.js for financial accuracy (28 digits of precision, standard rounding)
Decimal.set({ precision: 28, rounding: Decimal.ROUND_HALF_UP });

export class MoneyUtil {
  /**
   * Parse value into a safe Decimal instance
   */
  static from(value: string | number | Decimal): Decimal {
    return new Decimal(value);
  }

  /**
   * Format decimal to 2 decimal places for display (SAR)
   */
  static format(amount: string | number | Decimal, currency = 'SAR'): string {
    const d = new Decimal(amount);
    return `${d.toFixed(2)} ${currency}`;
  }

  /**
   * Round to standard currency cents (2 decimal places)
   */
  static round(amount: string | number | Decimal): Decimal {
    return new Decimal(amount).toDecimalPlaces(2, Decimal.ROUND_HALF_UP);
  }

  /**
   * Calculate Net Commission Base:
   * Base = Sum(Eligible Product Price * Quantity - Discounts)
   * Strictly excluding taxes and shipping
   */
  static calculateNetCommissionBase(
    eligibleProductsSubtotal: string | number | Decimal,
    eligibleDiscounts: string | number | Decimal = 0
  ): Decimal {
    const subtotal = new Decimal(eligibleProductsSubtotal);
    const discount = new Decimal(eligibleDiscounts);
    const net = subtotal.minus(discount);
    return net.greaterThanOrEqualTo(0) ? this.round(net) : new Decimal(0);
  }

  /**
   * Calculate Marketer Commission and Platform Fee
   */
  static calculateCommissions(params: {
    commissionType: 'PERCENTAGE' | 'FLAT_FEE';
    commissionValue: string | number | Decimal;
    commissionBase: string | number | Decimal;
    platformFeeRate: string | number | Decimal;
  }): {
    marketerAmount: Decimal;
    platformFeeAmount: Decimal;
    brandTotalCost: Decimal;
  } {
    const base = new Decimal(params.commissionBase);
    const feeRate = new Decimal(params.platformFeeRate);
    let marketerAmount = new Decimal(0);

    if (params.commissionType === 'PERCENTAGE') {
      const rate = new Decimal(params.commissionValue);
      // Value can be 10 for 10% or 0.10. If > 1, divide by 100
      const actualRate = rate.greaterThan(1) ? rate.dividedBy(100) : rate;
      marketerAmount = this.round(base.times(actualRate));
    } else {
      // Flat fee
      marketerAmount = this.round(new Decimal(params.commissionValue));
    }

    // Platform Fee = Base * PlatformFeeRate
    const platformFeeRateValue = feeRate.greaterThan(1) ? feeRate.dividedBy(100) : feeRate;
    const platformFeeAmount = this.round(base.times(platformFeeRateValue));

    // Brand Total Cost = Marketer Commission + Platform Fee
    const brandTotalCost = marketerAmount.plus(platformFeeAmount);

    return {
      marketerAmount,
      platformFeeAmount,
      brandTotalCost,
    };
  }

  /**
   * Proportional adjustment for partial refunds
   */
  static calculateProportionalRefund(params: {
    originalMarketerAmount: string | number | Decimal;
    originalPlatformFee: string | number | Decimal;
    refundedNetBase: string | number | Decimal;
    originalNetBase: string | number | Decimal;
  }): {
    reversedMarketerAmount: Decimal;
    reversedPlatformFee: Decimal;
    reversedTotalBrandRefund: Decimal;
  } {
    const originalNet = new Decimal(params.originalNetBase);
    const refundedNet = new Decimal(params.refundedNetBase);
    
    if (originalNet.isZero()) {
      return {
        reversedMarketerAmount: new Decimal(0),
        reversedPlatformFee: new Decimal(0),
        reversedTotalBrandRefund: new Decimal(0),
      };
    }

    const refundRatio = refundedNet.dividedBy(originalNet);
    const reversedMarketerAmount = this.round(new Decimal(params.originalMarketerAmount).times(refundRatio));
    const reversedPlatformFee = this.round(new Decimal(params.originalPlatformFee).times(refundRatio));
    const reversedTotalBrandRefund = reversedMarketerAmount.plus(reversedPlatformFee);

    return {
      reversedMarketerAmount,
      reversedPlatformFee,
      reversedTotalBrandRefund,
    };
  }
}
